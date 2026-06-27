import { Editor } from '@tiptap/core'
import StarterKit from '@tiptap/starter-kit'
import Link from '@tiptap/extension-link'
import Image from '@tiptap/extension-image'
import Placeholder from '@tiptap/extension-placeholder'

const emptyDoc = () => ({
  type: 'doc',
  content: [{ type: 'paragraph' }],
})

const parseContent = (value) => {
  if (!value.trim()) {
    return emptyDoc()
  }

  try {
    const parsed = JSON.parse(value)
    if (parsed && parsed.type === 'doc') {
      return parsed
    }
  } catch {
    // Existing Markdown is intentionally not auto-converted.
  }

  return {
    type: 'doc',
    content: value
      .split(/\n{2,}/)
      .filter((line) => line.trim() !== '')
      .map((line) => ({
        type: 'paragraph',
        content: [{ type: 'text', text: line.trim() }],
      })),
  }
}

const button = (label, action, options = {}) => {
  const element = document.createElement('button')
  element.type = 'button'
  element.textContent = label
  element.className = 'min-h-9 rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-bold text-slate-700 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-200'
  element.addEventListener('click', action)
  if (options.title) {
    element.title = options.title
  }
  return element
}

const syncBody = (editor, textarea) => {
  textarea.value = JSON.stringify(editor.getJSON())
}

const richTextToMarkdown = (node) => {
  if (!node || typeof node !== 'object') {
    return ''
  }

  if (node.type === 'text') {
    return node.text || ''
  }

  const children = Array.isArray(node.content)
    ? node.content.map((child) => richTextToMarkdown(child)).join('')
    : ''

  switch (node.type) {
    case 'doc':
      return Array.isArray(node.content)
        ? node.content.map((child) => richTextToMarkdown(child)).filter(Boolean).join('\n\n')
        : ''
    case 'paragraph':
      return children
    case 'heading':
      return `${'#'.repeat(Math.max(1, Math.min(Number(node.attrs?.level || 2), 6)))} ${children}`.trim()
    case 'bulletList':
    case 'orderedList':
      return Array.isArray(node.content)
        ? node.content.map((child, index) => richTextListItemToMarkdown(child, node.type === 'orderedList' ? `${index + 1}. ` : '- ')).join('\n')
        : ''
    case 'listItem':
      return children
    case 'blockquote':
      return children.split('\n').map((line) => `> ${line}`).join('\n')
    case 'codeBlock':
      return `\`\`\`\n${children}\n\`\`\``
    case 'horizontalRule':
      return '---'
    case 'hardBreak':
      return '\n'
    case 'image':
      return node.attrs?.src ? `![${node.attrs?.alt || ''}](${node.attrs.src})` : ''
    default:
      return children
  }
}

const richTextListItemToMarkdown = (node, marker) => {
  const text = richTextToMarkdown(node).split('\n').map((line, index) => index === 0 ? `${marker}${line}` : `  ${line}`).join('\n')

  return text.trimEnd()
}

const slashItems = [
  { label: '本文', hint: '通常の段落', keywords: ['paragraph', 'text'], run: (editor) => editor.chain().focus().setParagraph().run() },
  { label: '見出し1', hint: '# 見出し', keywords: ['h1', 'heading'], run: (editor) => editor.chain().focus().toggleHeading({ level: 1 }).run() },
  { label: '見出し2', hint: '## 見出し', keywords: ['h2', 'heading'], run: (editor) => editor.chain().focus().toggleHeading({ level: 2 }).run() },
  { label: '見出し3', hint: '### 見出し', keywords: ['h3', 'heading'], run: (editor) => editor.chain().focus().toggleHeading({ level: 3 }).run() },
  { label: '箇条書き', hint: 'リスト', keywords: ['bullet', 'list'], run: (editor) => editor.chain().focus().toggleBulletList().run() },
  { label: '番号リスト', hint: '順序付きリスト', keywords: ['ordered', 'number', 'list'], run: (editor) => editor.chain().focus().toggleOrderedList().run() },
  { label: '引用', hint: '引用ブロック', keywords: ['quote', 'blockquote'], run: (editor) => editor.chain().focus().toggleBlockquote().run() },
  { label: 'コード', hint: 'コードブロック', keywords: ['code'], run: (editor) => editor.chain().focus().toggleCodeBlock().run() },
  { label: '区切り線', hint: '水平線', keywords: ['hr', 'rule'], run: (editor) => editor.chain().focus().setHorizontalRule().run() },
  {
    label: '画像',
    hint: '画像URLを挿入',
    keywords: ['image', 'img'],
    run: (editor) => {
      const src = window.prompt('画像URLまたは /uploads/... のパス')
      if (src && src.trim() !== '') {
        editor.chain().focus().setImage({ src: src.trim(), alt: '' }).run()
      }
    },
  },
]

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-rich-editor]').forEach((root) => {
    const textarea = root.querySelector('[data-rich-editor-source]')
    const mount = root.querySelector('[data-rich-editor-mount]')
    const toolbar = root.querySelector('[data-rich-editor-toolbar]')
    const shell = root.querySelector('[data-rich-editor-shell]')
    const radios = Array.from(document.querySelectorAll('input[name="body_format"]'))

    if (!(textarea instanceof HTMLTextAreaElement) || !(mount instanceof HTMLElement) || !(toolbar instanceof HTMLElement) || !(shell instanceof HTMLElement)) {
      return
    }

    const slashMenu = document.createElement('div')
    slashMenu.className = 'absolute z-30 hidden w-72 overflow-hidden rounded-lg border border-slate-200 bg-white py-2 shadow-xl'
    slashMenu.setAttribute('role', 'listbox')
    shell.append(slashMenu)

    let slashState = null
    let selectedSlashIndex = 0
    let filteredSlashItems = []
    let currentMode = radios.find((radio) => radio.checked)?.value || 'markdown'
    const initialBody = textarea.value
    const drafts = {
      markdown: currentMode === 'markdown' ? initialBody : '',
      html: currentMode === 'html' ? initialBody : '',
      rich_text: currentMode === 'rich_text' ? initialBody : '',
    }

    const hideSlashMenu = () => {
      slashMenu.classList.add('hidden')
      slashState = null
      selectedSlashIndex = 0
      filteredSlashItems = []
    }

    const runSlashItem = (item) => {
      if (!slashState) {
        return
      }

      editor.chain().focus().deleteRange({ from: slashState.from, to: slashState.to }).run()
      item.run(editor)
      hideSlashMenu()
    }

    const renderSlashMenu = () => {
      slashMenu.textContent = ''
      filteredSlashItems.forEach((item, index) => {
        const option = document.createElement('button')
        option.type = 'button'
        option.className = `grid w-full gap-0.5 px-4 py-2 text-left hover:bg-slate-100 ${index === selectedSlashIndex ? 'bg-slate-100' : ''}`
        option.setAttribute('role', 'option')
        option.setAttribute('aria-selected', index === selectedSlashIndex ? 'true' : 'false')
        option.innerHTML = `<span class="text-sm font-bold text-slate-900">${item.label}</span><span class="text-xs text-slate-500">${item.hint}</span>`
        option.addEventListener('mousedown', (event) => {
          event.preventDefault()
          runSlashItem(item)
        })
        slashMenu.append(option)
      })
    }

    const updateSlashMenu = () => {
      const current = radios.find((radio) => radio.checked)?.value || 'markdown'
      if (current !== 'rich_text') {
        hideSlashMenu()
        return
      }

      const { state, view } = editor
      const { from, empty } = state.selection
      if (!empty) {
        hideSlashMenu()
        return
      }

      const $from = state.selection.$from
      const blockStart = $from.start()
      const textBeforeCursor = state.doc.textBetween(blockStart, from, '\n', '\n')
      const match = textBeforeCursor.match(/^\/([\p{L}\p{N}_-]*)?$/u)
      if (!match) {
        hideSlashMenu()
        return
      }

      const query = (match[1] || '').toLowerCase()
      filteredSlashItems = slashItems.filter((item) => {
        const haystack = [item.label, item.hint, ...item.keywords].join(' ').toLowerCase()
        return query === '' || haystack.includes(query)
      })
      if (filteredSlashItems.length === 0) {
        hideSlashMenu()
        return
      }

      slashState = { from: from - textBeforeCursor.length, to: from }
      selectedSlashIndex = Math.min(selectedSlashIndex, filteredSlashItems.length - 1)
      renderSlashMenu()

      const caret = view.coordsAtPos(from)
      const shellRect = shell.getBoundingClientRect()
      slashMenu.style.left = `${Math.max(12, Math.min(caret.left - shellRect.left, shellRect.width - 300))}px`
      slashMenu.style.top = `${caret.bottom - shellRect.top + 8}px`
      slashMenu.classList.remove('hidden')
    }

    const editor = new Editor({
      element: mount,
      content: parseContent(textarea.value),
      extensions: [
        StarterKit.configure({
          heading: { levels: [1, 2, 3, 4] },
        }),
        Link.configure({
          autolink: true,
          defaultProtocol: 'https',
          openOnClick: false,
        }),
        Image.configure({
          allowBase64: false,
        }),
        Placeholder.configure({
          placeholder: '本文を入力',
        }),
      ],
      editorProps: {
        attributes: {
          class: 'min-h-[28rem] px-5 py-4 text-base leading-8 text-slate-900 focus:outline-none',
        },
        handleKeyDown(view, event) {
          if (slashMenu.classList.contains('hidden')) {
            return false
          }
          if (event.key === 'ArrowDown') {
            event.preventDefault()
            selectedSlashIndex = (selectedSlashIndex + 1) % filteredSlashItems.length
            renderSlashMenu()
            return true
          }
          if (event.key === 'ArrowUp') {
            event.preventDefault()
            selectedSlashIndex = (selectedSlashIndex - 1 + filteredSlashItems.length) % filteredSlashItems.length
            renderSlashMenu()
            return true
          }
          if (event.key === 'Enter') {
            event.preventDefault()
            runSlashItem(filteredSlashItems[selectedSlashIndex])
            return true
          }
          if (event.key === 'Escape') {
            event.preventDefault()
            hideSlashMenu()
            return true
          }
          return false
        },
      },
      onUpdate: ({ editor: activeEditor }) => {
        drafts.rich_text = JSON.stringify(activeEditor.getJSON())
        updateSlashMenu()
      },
      onSelectionUpdate: updateSlashMenu,
    })

    if (currentMode === 'rich_text') {
      drafts.rich_text = JSON.stringify(editor.getJSON())
    }

    toolbar.append(
      button('H1', () => editor.chain().focus().toggleHeading({ level: 1 }).run(), { title: '見出し1' }),
      button('H2', () => editor.chain().focus().toggleHeading({ level: 2 }).run(), { title: '見出し2' }),
      button('H3', () => editor.chain().focus().toggleHeading({ level: 3 }).run(), { title: '見出し3' }),
      button('B', () => editor.chain().focus().toggleBold().run(), { title: '太字' }),
      button('I', () => editor.chain().focus().toggleItalic().run(), { title: '斜体' }),
      button('箇条書き', () => editor.chain().focus().toggleBulletList().run()),
      button('番号', () => editor.chain().focus().toggleOrderedList().run()),
      button('引用', () => editor.chain().focus().toggleBlockquote().run()),
      button('コード', () => editor.chain().focus().toggleCodeBlock().run()),
      button('リンク', () => {
        const previous = editor.getAttributes('link').href || ''
        const href = window.prompt('URL', previous)
        if (href === null) {
          return
        }
        if (href.trim() === '') {
          editor.chain().focus().unsetLink().run()
          return
        }
        editor.chain().focus().extendMarkRange('link').setLink({ href: href.trim() }).run()
      }),
      button('画像', () => {
        const src = window.prompt('画像URLまたは /uploads/... のパス')
        if (!src || src.trim() === '') {
          return
        }
        editor.chain().focus().setImage({ src: src.trim(), alt: '' }).run()
      }),
    )

    const saveCurrentDraft = () => {
      if (currentMode === 'rich_text') {
        drafts.rich_text = JSON.stringify(editor.getJSON())
        return
      }

      drafts[currentMode] = textarea.value
    }

    const bodyFromRichTextFor = (mode) => {
      if (mode === 'html') {
        return editor.getHTML()
      }

      return richTextToMarkdown(editor.getJSON())
    }

    const updateMode = () => {
      const current = radios.find((radio) => radio.checked)?.value || 'markdown'
      saveCurrentDraft()
      if (current !== 'rich_text' && drafts[current] === '' && currentMode === 'rich_text') {
        drafts[current] = bodyFromRichTextFor(current)
      }
      currentMode = current
      const rich = current === 'rich_text'
      root.classList.toggle('is-rich-mode', rich)
      mount.closest('[data-rich-editor-shell]')?.classList.toggle('hidden', !rich)
      toolbar.classList.toggle('hidden', !rich)
      textarea.classList.toggle('hidden', rich)
      textarea.required = true
      if (rich) {
        if (drafts.rich_text !== '') {
          editor.commands.setContent(parseContent(drafts.rich_text), false)
        } else if (textarea.value.trim() !== '') {
          editor.commands.setContent(parseContent(textarea.value), false)
          drafts.rich_text = JSON.stringify(editor.getJSON())
        }
        updateSlashMenu()
      } else {
        textarea.value = drafts[current] || ''
        hideSlashMenu()
      }
    }

    for (const radio of radios) {
      radio.addEventListener('change', updateMode)
    }

    textarea.form?.addEventListener('submit', () => {
      if ((radios.find((radio) => radio.checked)?.value || 'markdown') === 'rich_text') {
        syncBody(editor, textarea)
      } else {
        saveCurrentDraft()
        textarea.value = drafts[currentMode] || ''
      }
    })

    updateMode()

    document.addEventListener('mousedown', (event) => {
      if (!root.contains(event.target)) {
        hideSlashMenu()
      }
    })
  })
})
