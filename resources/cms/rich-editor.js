import { Editor } from '@tiptap/core'
import StarterKit from '@tiptap/starter-kit'
import Link from '@tiptap/extension-link'
import Image from '@tiptap/extension-image'
import Placeholder from '@tiptap/extension-placeholder'
import Underline from '@tiptap/extension-underline'
import { Table } from '@tiptap/extension-table'
import { TableCell } from '@tiptap/extension-table-cell'
import { TableHeader } from '@tiptap/extension-table-header'
import { TableRow } from '@tiptap/extension-table-row'

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

const icon = (path, options = {}) => {
  const label = options.label || ''
  const viewBox = options.viewBox || '0 0 24 24'

  return `
    <svg aria-hidden="true" viewBox="${viewBox}" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      ${path}
    </svg>
    <span class="sr-only">${escapeHtml(label)}</span>
  `
}

const isLikelyUrl = (value) => /^(https?:\/\/|\/)[^\s]+$/i.test(value.trim())

const escapeHtml = (value) => value
  .replace(/&/g, '&amp;')
  .replace(/</g, '&lt;')
  .replace(/>/g, '&gt;')
  .replace(/"/g, '&quot;')
  .replace(/'/g, '&#039;')

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
    case 'table':
      return richTextTableToMarkdown(node)
    case 'tableRow':
      return Array.isArray(node.content)
        ? node.content.map((child) => richTextToMarkdown(child).replace(/\|/g, '\\|')).join(' | ')
        : ''
    case 'tableHeader':
    case 'tableCell':
      return children
    default:
      return children
  }
}

const richTextTableToMarkdown = (node) => {
  const rows = Array.isArray(node.content) ? node.content : []
  if (rows.length === 0) {
    return ''
  }

  const lines = rows.map((row) => `| ${richTextToMarkdown(row)} |`)
  const firstRow = Array.isArray(rows[0]?.content) ? rows[0].content : []
  const separator = `| ${firstRow.map(() => '---').join(' | ')} |`

  return [lines[0], separator, ...lines.slice(1)].join('\n')
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
  { label: '表', hint: '3 x 3 テーブル', keywords: ['table'], run: (editor) => editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run() },
  {
    label: '画像',
    hint: '登録済み画像を挿入',
    keywords: ['image', 'img'],
    run: (_editor, context) => context?.openImagePicker?.(),
  },
]

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-rich-editor]').forEach((root) => {
    const textarea = root.querySelector('[data-rich-editor-source]')
    const mount = root.querySelector('[data-rich-editor-mount]')
    const toolbar = root.querySelector('[data-rich-editor-toolbar]')
    const shell = root.querySelector('[data-rich-editor-shell]')
    const imageLibrary = root.querySelector('[data-rich-image-library]')
    const radios = Array.from(document.querySelectorAll('input[name="body_format"]'))

    if (!(textarea instanceof HTMLTextAreaElement) || !(mount instanceof HTMLElement) || !(toolbar instanceof HTMLElement) || !(shell instanceof HTMLElement)) {
      return
    }

    const slashMenu = document.createElement('div')
    slashMenu.className = 'absolute z-30 hidden w-72 overflow-hidden rounded-lg border border-slate-200 bg-white py-2 shadow-xl'
    slashMenu.setAttribute('role', 'listbox')
    shell.append(slashMenu)

    const imageItems = imageLibrary instanceof HTMLElement
      ? Array.from(imageLibrary.querySelectorAll('[data-rich-image-option]')).map((option) => ({
        src: option.dataset.src || '',
        name: option.dataset.name || '',
        alt: option.dataset.alt || '',
      })).filter((item) => item.src !== '')
      : []
    const imagePanel = document.createElement('div')
    imagePanel.className = 'absolute left-3 right-3 top-16 z-20 hidden rounded-lg border border-slate-200 bg-white p-4 shadow-xl'
    imagePanel.innerHTML = `
      <div class="grid gap-3">
        <div class="flex flex-wrap items-end justify-between gap-3">
          <label class="min-w-56 flex-1 text-sm font-bold text-slate-700">登録済み画像
            <input class="mt-1" type="search" autocomplete="off" placeholder="画像名・代替テキストで検索" data-rich-image-search>
          </label>
          <button class="min-h-9 rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-bold text-slate-700 hover:bg-slate-100" type="button" data-rich-image-close>閉じる</button>
        </div>
        <div class="max-h-72 overflow-y-auto pr-1 [scrollbar-gutter:stable]" data-rich-image-list></div>
        <form class="grid gap-2 border-t border-slate-200 pt-3" data-rich-image-manual>
          <label class="text-sm font-bold text-slate-700">画像URLまたは /uploads/... のパス
            <input class="mt-1" name="src" autocomplete="off" placeholder="/uploads/media/example.webp">
          </label>
          <label class="text-sm font-bold text-slate-700">代替テキスト
            <input class="mt-1" name="alt" autocomplete="off">
          </label>
          <div class="flex justify-end">
            <button class="min-h-9 rounded-md bg-blue-600 px-3 py-1.5 text-sm font-bold text-white hover:bg-blue-700" type="submit">挿入</button>
          </div>
        </form>
      </div>
    `
    shell.append(imagePanel)
    const imageSearch = imagePanel.querySelector('[data-rich-image-search]')
    const imageList = imagePanel.querySelector('[data-rich-image-list]')
    const imageManualForm = imagePanel.querySelector('[data-rich-image-manual]')
    const imageManualSrc = imageManualForm?.querySelector('input[name="src"]')
    const imageManualAlt = imageManualForm?.querySelector('input[name="alt"]')
    const selectionMenu = document.createElement('div')
    selectionMenu.className = 'absolute z-30 hidden rounded-lg border border-slate-200 bg-white p-2 shadow-xl'
    selectionMenu.innerHTML = `
      <div class="flex flex-wrap items-center gap-1" data-selection-format-row>
        <button class="inline-flex h-8 w-8 items-center justify-center rounded-md text-slate-800 hover:bg-slate-100" type="button" data-selection-action="bold" title="太字" aria-label="太字">${icon('<path d="M7 5h6a4 4 0 0 1 0 8H7z"></path><path d="M7 13h7a4 4 0 0 1 0 8H7z"></path>', { label: '太字' })}</button>
        <button class="inline-flex h-8 w-8 items-center justify-center rounded-md text-slate-800 hover:bg-slate-100" type="button" data-selection-action="italic" title="斜体" aria-label="斜体">${icon('<path d="M19 4h-9"></path><path d="M14 20H5"></path><path d="M15 4 9 20"></path>', { label: '斜体' })}</button>
        <button class="inline-flex h-8 w-8 items-center justify-center rounded-md text-slate-800 hover:bg-slate-100" type="button" data-selection-action="underline" title="下線" aria-label="下線">${icon('<path d="M6 4v6a6 6 0 0 0 12 0V4"></path><path d="M4 21h16"></path>', { label: '下線' })}</button>
        <button class="inline-flex h-8 w-8 items-center justify-center rounded-md text-slate-800 hover:bg-slate-100" type="button" data-selection-action="strike" title="取り消し線" aria-label="取り消し線">${icon('<path d="M16 4H9a3 3 0 0 0-2.83 4"></path><path d="M14 12a3 3 0 0 1 0 6H6"></path><path d="M4 12h16"></path>', { label: '取り消し線' })}</button>
        <button class="inline-flex h-8 w-8 items-center justify-center rounded-md text-slate-800 hover:bg-slate-100" type="button" data-selection-action="code" title="コード" aria-label="コード">${icon('<path d="m8 9-4 3 4 3"></path><path d="m16 9 4 3-4 3"></path><path d="m14 5-4 14"></path>', { label: 'コード' })}</button>
        <button class="inline-flex h-8 w-8 items-center justify-center rounded-md text-blue-700 hover:bg-blue-50" type="button" data-selection-action="link" title="リンク" aria-label="リンク">${icon('<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>', { label: 'リンク' })}</button>
      </div>
      <form class="mt-2 hidden grid min-w-72 grid-cols-[minmax(0,1fr)_auto_auto] gap-2 border-t border-slate-200 pt-2" data-selection-link-form>
        <input class="min-h-9 rounded-md border border-slate-300 px-3 py-1.5 text-sm" name="href" autocomplete="off" placeholder="https://example.com">
        <button class="min-h-9 rounded-md bg-blue-600 px-3 py-1.5 text-sm font-bold text-white hover:bg-blue-700" type="submit">設定</button>
        <button class="min-h-9 rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-bold text-slate-700 hover:bg-slate-100" type="button" data-selection-unlink>解除</button>
      </form>
    `
    shell.append(selectionMenu)
    const selectionLinkForm = selectionMenu.querySelector('[data-selection-link-form]')
    const selectionLinkInput = selectionLinkForm?.querySelector('input[name="href"]')
    const selectionFormatRow = selectionMenu.querySelector('[data-selection-format-row]')

    let slashState = null
    let selectedSlashIndex = 0
    let filteredSlashItems = []
    let isMouseSelecting = false
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

    const hideSelectionMenu = () => {
      selectionMenu.classList.add('hidden')
      selectionLinkForm?.classList.add('hidden')
    }

    const closeImagePicker = () => {
      imagePanel.classList.add('hidden')
    }

    const insertImage = (src, alt = '') => {
      const normalized = src.trim()
      if (normalized === '') {
        return
      }

      editor.chain().focus().setImage({ src: normalized, alt: alt.trim() }).run()
      closeImagePicker()
    }

    const renderImageList = () => {
      if (!(imageList instanceof HTMLElement)) {
        return
      }
      const keyword = imageSearch instanceof HTMLInputElement ? imageSearch.value.trim().toLowerCase() : ''
      const matched = imageItems.filter((item) => {
        const haystack = `${item.name} ${item.alt}`.toLowerCase()
        return keyword === '' || haystack.includes(keyword)
      })

      imageList.textContent = ''
      if (matched.length === 0) {
        const empty = document.createElement('div')
        empty.className = 'rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center text-sm font-bold text-slate-500'
        empty.textContent = imageItems.length === 0 ? '登録済み画像はありません。' : '一致する画像はありません。'
        imageList.append(empty)
        return
      }

      const grid = document.createElement('div')
      grid.className = 'grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4'
      for (const item of matched) {
        const option = document.createElement('button')
        option.type = 'button'
        option.className = 'overflow-hidden rounded-lg border border-slate-200 bg-white text-left shadow-sm hover:border-blue-400 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-blue-300'
        option.innerHTML = `
          <img class="aspect-[4/3] w-full bg-slate-100 object-cover" src="${escapeHtml(item.src)}" alt="${escapeHtml(item.alt || item.name)}" loading="lazy">
          <span class="block truncate px-2 py-1.5 text-xs font-bold text-slate-700">${escapeHtml(item.name)}</span>
        `
        option.addEventListener('click', () => insertImage(item.src, item.alt))
        grid.append(option)
      }
      imageList.append(grid)
    }

    const openImagePicker = () => {
      hideSlashMenu()
      renderImageList()
      imagePanel.classList.remove('hidden')
      if (imageSearch instanceof HTMLInputElement) {
        imageSearch.focus()
      }
    }

    const updateSelectionMenuState = () => {
      selectionMenu.querySelectorAll('[data-selection-action]').forEach((item) => {
        if (!(item instanceof HTMLElement)) {
          return
        }

        const action = item.dataset.selectionAction || ''
        const active = action === 'link' ? editor.isActive('link') : editor.isActive(action)
        item.classList.toggle('bg-slate-900', active)
        item.classList.toggle('text-white', active)
        item.classList.toggle('hover:bg-slate-800', active)
        item.classList.toggle('text-blue-700', action === 'link' && !active)
      })

      if (selectionLinkInput instanceof HTMLInputElement) {
        selectionLinkInput.value = editor.getAttributes('link').href || ''
      }
    }

    const updateSelectionMenu = () => {
      if (isMouseSelecting) {
        hideSelectionMenu()
        return
      }

      const current = radios.find((radio) => radio.checked)?.value || 'markdown'
      if (current !== 'rich_text' || !editor.isEditable || !editor.isFocused) {
        hideSelectionMenu()
        return
      }

      const { from, to, empty } = editor.state.selection
      if (empty || from === to || !selectionFormatRow) {
        hideSelectionMenu()
        return
      }

      hideSlashMenu()
      closeImagePicker()
      updateSelectionMenuState()

      const start = editor.view.coordsAtPos(from)
      const end = editor.view.coordsAtPos(to)
      const shellRect = shell.getBoundingClientRect()
      selectionMenu.style.visibility = 'hidden'
      selectionMenu.classList.remove('hidden')
      const center = (start.left + end.right) / 2 - shellRect.left
      const menuWidth = selectionMenu.offsetWidth || 360
      const left = Math.max(12, Math.min(center - menuWidth / 2, shellRect.width - menuWidth - 12))
      const top = Math.max(12, start.top - shellRect.top - selectionMenu.offsetHeight - 10)
      selectionMenu.style.left = `${left}px`
      selectionMenu.style.top = `${top}px`
      selectionMenu.style.visibility = ''
    }

    const openSelectionLinkForm = () => {
      updateSelectionMenuState()
      selectionLinkForm?.classList.remove('hidden')
      if (selectionLinkInput instanceof HTMLInputElement) {
        selectionLinkInput.focus()
        selectionLinkInput.select()
      }
    }

    const runSlashItem = (item) => {
      if (!slashState) {
        return
      }

      editor.chain().focus().deleteRange({ from: slashState.from, to: slashState.to }).run()
      item.run(editor, { openImagePicker })
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
        option.innerHTML = `<span class="text-sm font-bold text-slate-900">${escapeHtml(item.label)}</span><span class="text-xs text-slate-500">${escapeHtml(item.hint)}</span>`
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
        hideSelectionMenu()
        return
      }

      const { state, view } = editor
      const { from, empty } = state.selection
      if (!empty) {
        hideSlashMenu()
        updateSelectionMenu()
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
          enableClickSelection: true,
        }),
        Underline,
        Image.configure({
          allowBase64: false,
        }),
        Table.configure({
          resizable: true,
        }),
        TableRow,
        TableHeader,
        TableCell,
        Placeholder.configure({
          placeholder: '本文を入力',
        }),
      ],
      editorProps: {
        attributes: {
          class: 'min-h-[28rem] px-5 py-4 text-base leading-8 text-slate-900 focus:outline-none',
        },
        handleClick(view, pos, event) {
          if (event.target instanceof Element && event.target.closest('a')) {
            event.preventDefault()
            return true
          }

          return false
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
        handlePaste(view, event) {
          const pasted = event.clipboardData?.getData('text/plain')?.trim() || ''
          if (!isLikelyUrl(pasted) || view.state.selection.empty) {
            return false
          }

          event.preventDefault()
          editor.chain().focus().setLink({ href: pasted }).run()
          return true
        },
      },
      onUpdate: ({ editor: activeEditor }) => {
        drafts.rich_text = JSON.stringify(activeEditor.getJSON())
        updateSlashMenu()
        updateSelectionMenu()
      },
      onSelectionUpdate: () => {
        updateSlashMenu()
        updateSelectionMenu()
      },
      onBlur: ({ event }) => {
        if (event.relatedTarget instanceof Node && selectionMenu.contains(event.relatedTarget)) {
          return
        }

        window.setTimeout(() => {
          if (!selectionMenu.contains(document.activeElement)) {
            hideSelectionMenu()
          }
        }, 0)
      },
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
      button('区切り線', () => editor.chain().focus().setHorizontalRule().run(), { title: '区切り線を挿入' }),
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
      button('画像', openImagePicker, { title: '登録済み画像またはURLを挿入' }),
      button('表', () => editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run(), { title: '3 x 3 の表を挿入' }),
      button('行+', () => editor.chain().focus().addRowAfter().run(), { title: '下に行を追加' }),
      button('列+', () => editor.chain().focus().addColumnAfter().run(), { title: '右に列を追加' }),
      button('行削除', () => editor.chain().focus().deleteRow().run()),
      button('列削除', () => editor.chain().focus().deleteColumn().run()),
      button('表削除', () => editor.chain().focus().deleteTable().run()),
    )

    imageSearch?.addEventListener('input', renderImageList)
    imagePanel.querySelector('[data-rich-image-close]')?.addEventListener('click', closeImagePicker)
    imageManualForm?.addEventListener('submit', (event) => {
      event.preventDefault()
      if (imageManualSrc instanceof HTMLInputElement && imageManualAlt instanceof HTMLInputElement) {
        insertImage(imageManualSrc.value, imageManualAlt.value)
      }
    })
    selectionMenu.addEventListener('mousedown', (event) => {
      const target = event.target
      if (target instanceof Element && !target.closest('input')) {
        event.preventDefault()
      }
    })
    selectionMenu.addEventListener('click', (event) => {
      const target = event.target
      if (!(target instanceof Element)) {
        return
      }

      const button = target.closest('[data-selection-action]')
      if (button instanceof HTMLElement) {
        const action = button.dataset.selectionAction || ''
        if (action === 'bold') {
          editor.chain().focus().toggleBold().run()
        } else if (action === 'italic') {
          editor.chain().focus().toggleItalic().run()
        } else if (action === 'underline') {
          editor.chain().focus().toggleUnderline().run()
        } else if (action === 'strike') {
          editor.chain().focus().toggleStrike().run()
        } else if (action === 'code') {
          editor.chain().focus().toggleCode().run()
        } else if (action === 'link') {
          openSelectionLinkForm()
          return
        }

        updateSelectionMenu()
      }
    })
    selectionLinkForm?.addEventListener('submit', (event) => {
      event.preventDefault()
      if (!(selectionLinkInput instanceof HTMLInputElement)) {
        return
      }

      const href = selectionLinkInput.value.trim()
      if (href === '') {
        editor.chain().focus().unsetLink().run()
      } else {
        editor.chain().focus().extendMarkRange('link').setLink({ href }).run()
      }
      hideSelectionMenu()
    })
    selectionMenu.querySelector('[data-selection-unlink]')?.addEventListener('click', () => {
      editor.chain().focus().unsetLink().run()
      hideSelectionMenu()
    })

    mount.addEventListener('mousedown', (event) => {
      if (event.button !== 0 || !(event.target instanceof Element) || !event.target.closest('.ProseMirror')) {
        return
      }

      isMouseSelecting = true
      hideSelectionMenu()
    })

    document.addEventListener('mouseup', () => {
      if (!isMouseSelecting) {
        return
      }

      isMouseSelecting = false
      window.setTimeout(() => {
        updateSlashMenu()
        updateSelectionMenu()
      }, 0)
    })

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
        hideSelectionMenu()
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
        hideSelectionMenu()
        closeImagePicker()
      }
    })
  })
})
