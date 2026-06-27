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

import { parseContent } from './rich-editor/content.js'
import { isLikelyUrl } from './rich-editor/dom.js'
import { createFormatSwitcher } from './rich-editor/format-switcher.js'
import { createImagePicker } from './rich-editor/image-picker.js'
import { createSelectionMenu } from './rich-editor/selection-menu.js'
import { createSlashMenu } from './rich-editor/slash-menu.js'
import { appendToolbar } from './rich-editor/toolbar.js'

const createEditor = ({ mount, textarea, refreshFloatingMenus, getEditor, getSlashMenu, getSelectionMenu }) => new Editor({
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
      return getSlashMenu()?.handleKeyDown(event) || false
    },
    handlePaste(view, event) {
      const pasted = event.clipboardData?.getData('text/plain')?.trim() || ''
      if (!isLikelyUrl(pasted) || view.state.selection.empty) {
        return false
      }

      event.preventDefault()
      getEditor()?.chain().focus().setLink({ href: pasted }).run()
      return true
    },
  },
  onUpdate: refreshFloatingMenus,
  onSelectionUpdate: refreshFloatingMenus,
  onBlur: ({ event }) => {
    const selectionMenu = getSelectionMenu()
    if (event.relatedTarget instanceof Node && selectionMenu?.contains(event.relatedTarget)) {
      return
    }

    window.setTimeout(() => {
      if (!selectionMenu?.contains(document.activeElement)) {
        selectionMenu?.hide()
      }
    }, 0)
  },
})

const initializeRichEditor = (root) => {
  const textarea = root.querySelector('[data-rich-editor-source]')
  const mount = root.querySelector('[data-rich-editor-mount]')
  const toolbar = root.querySelector('[data-rich-editor-toolbar]')
  const shell = root.querySelector('[data-rich-editor-shell]')
  const imageLibrary = root.querySelector('[data-rich-image-library]')
  const radios = Array.from(document.querySelectorAll('input[name="body_format"]'))

  if (!(textarea instanceof HTMLTextAreaElement) || !(mount instanceof HTMLElement) || !(toolbar instanceof HTMLElement) || !(shell instanceof HTMLElement)) {
    return
  }

  let slashMenu = null
  let selectionMenu = null
  let formatSwitcher = null
  const checkedMode = () => radios.find((radio) => radio.checked)?.value || 'markdown'
  const currentMode = () => formatSwitcher?.getMode() || checkedMode()
  const refreshFloatingMenus = () => {
    slashMenu?.update()
    selectionMenu?.update()
  }

  let editor = null
  editor = createEditor({
    mount,
    textarea,
    refreshFloatingMenus,
    getEditor: () => editor,
    getSlashMenu: () => slashMenu,
    getSelectionMenu: () => selectionMenu,
  })

  const imagePicker = createImagePicker({
    shell,
    imageLibrary,
    onSelect: (src, alt) => editor.chain().focus().setImage({ src, alt }).run(),
  })
  const openImagePicker = () => {
    slashMenu?.hide()
    selectionMenu?.hide()
    imagePicker.open()
  }

  selectionMenu = createSelectionMenu({
    editor,
    shell,
    mount,
    getMode: currentMode,
    hideSlashMenu: () => slashMenu?.hide(),
    closeImagePicker: imagePicker.close,
  })
  slashMenu = createSlashMenu({
    editor,
    shell,
    getMode: currentMode,
    openImagePicker,
    hideSelectionMenu: () => selectionMenu?.hide(),
  })
  appendToolbar({ toolbar, editor, openImagePicker })
  formatSwitcher = createFormatSwitcher({
    root,
    textarea,
    mount,
    toolbar,
    radios,
    editor,
    hideSlashMenu: () => slashMenu?.hide(),
    hideSelectionMenu: () => selectionMenu?.hide(),
    onModeChanged: refreshFloatingMenus,
  })
  formatSwitcher.update()

  document.addEventListener('mousedown', (event) => {
    if (!(event.target instanceof Node) || !root.contains(event.target)) {
      slashMenu?.hide()
      selectionMenu?.hide()
      imagePicker.close()
    }
  })
}

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-rich-editor]').forEach(initializeRichEditor)
})
