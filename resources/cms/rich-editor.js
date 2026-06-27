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

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-rich-editor]').forEach((root) => {
    const textarea = root.querySelector('[data-rich-editor-source]')
    const mount = root.querySelector('[data-rich-editor-mount]')
    const toolbar = root.querySelector('[data-rich-editor-toolbar]')
    const radios = Array.from(document.querySelectorAll('input[name="body_format"]'))

    if (!(textarea instanceof HTMLTextAreaElement) || !(mount instanceof HTMLElement) || !(toolbar instanceof HTMLElement)) {
      return
    }

    const editor = new Editor({
      element: mount,
      content: parseContent(textarea.value),
      extensions: [
        StarterKit.configure({
          heading: { levels: [2, 3, 4] },
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
      },
      onUpdate: ({ editor: activeEditor }) => syncBody(activeEditor, textarea),
    })

    toolbar.append(
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

    const updateMode = () => {
      const current = radios.find((radio) => radio.checked)?.value || 'markdown'
      const rich = current === 'rich_text'
      root.classList.toggle('is-rich-mode', rich)
      mount.closest('[data-rich-editor-shell]')?.classList.toggle('hidden', !rich)
      toolbar.classList.toggle('hidden', !rich)
      textarea.classList.toggle('hidden', rich)
      textarea.required = true
      if (rich) {
        syncBody(editor, textarea)
      }
    }

    for (const radio of radios) {
      radio.addEventListener('change', updateMode)
    }

    textarea.form?.addEventListener('submit', () => {
      if ((radios.find((radio) => radio.checked)?.value || 'markdown') === 'rich_text') {
        syncBody(editor, textarea)
      }
    })

    updateMode()
  })
})
