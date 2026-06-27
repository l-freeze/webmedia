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

const selectionActions = [
  {
    name: 'bold',
    label: '太字',
    classes: 'text-slate-800 hover:bg-slate-100',
    icon: '<path d="M7 5h6a4 4 0 0 1 0 8H7z"></path><path d="M7 13h7a4 4 0 0 1 0 8H7z"></path>',
    run: (editor) => editor.chain().focus().toggleBold().run(),
  },
  {
    name: 'italic',
    label: '斜体',
    classes: 'text-slate-800 hover:bg-slate-100',
    icon: '<path d="M19 4h-9"></path><path d="M14 20H5"></path><path d="M15 4 9 20"></path>',
    run: (editor) => editor.chain().focus().toggleItalic().run(),
  },
  {
    name: 'underline',
    label: '下線',
    classes: 'text-slate-800 hover:bg-slate-100',
    icon: '<path d="M6 4v6a6 6 0 0 0 12 0V4"></path><path d="M4 21h16"></path>',
    run: (editor) => editor.chain().focus().toggleUnderline().run(),
  },
  {
    name: 'strike',
    label: '取り消し線',
    classes: 'text-slate-800 hover:bg-slate-100',
    icon: '<path d="M16 4H9a3 3 0 0 0-2.83 4"></path><path d="M14 12a3 3 0 0 1 0 6H6"></path><path d="M4 12h16"></path>',
    run: (editor) => editor.chain().focus().toggleStrike().run(),
  },
  {
    name: 'code',
    label: 'コード',
    classes: 'text-slate-800 hover:bg-slate-100',
    icon: '<path d="m8 9-4 3 4 3"></path><path d="m16 9 4 3-4 3"></path><path d="m14 5-4 14"></path>',
    run: (editor) => editor.chain().focus().toggleCode().run(),
  },
  {
    name: 'link',
    label: 'リンク',
    classes: 'text-blue-700 hover:bg-blue-50',
    icon: '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>',
  },
]

const selectionButton = (action) => `
  <button class="inline-flex h-8 w-8 items-center justify-center rounded-md ${action.classes}" type="button" data-selection-action="${action.name}" title="${escapeHtml(action.label)}" aria-label="${escapeHtml(action.label)}">
    ${icon(action.icon, { label: action.label })}
  </button>
`

const createImagePicker = ({ shell, imageLibrary, onSelect }) => {
  const imageItems = imageLibrary instanceof HTMLElement
    ? Array.from(imageLibrary.querySelectorAll('[data-rich-image-option]')).map((option) => ({
      src: option.dataset.src || '',
      name: option.dataset.name || '',
      alt: option.dataset.alt || '',
    })).filter((item) => item.src !== '')
    : []
  const panel = document.createElement('div')
  panel.className = 'absolute left-3 right-3 top-16 z-20 hidden rounded-lg border border-slate-200 bg-white p-4 shadow-xl'
  panel.innerHTML = `
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
  shell.append(panel)

  const search = panel.querySelector('[data-rich-image-search]')
  const list = panel.querySelector('[data-rich-image-list]')
  const manualForm = panel.querySelector('[data-rich-image-manual]')
  const manualSrc = manualForm?.querySelector('input[name="src"]')
  const manualAlt = manualForm?.querySelector('input[name="alt"]')

  const close = () => {
    panel.classList.add('hidden')
  }

  const insert = (src, alt = '') => {
    const normalized = src.trim()
    if (normalized === '') {
      return
    }

    onSelect(normalized, alt.trim())
    close()
  }

  const render = () => {
    if (!(list instanceof HTMLElement)) {
      return
    }
    const keyword = search instanceof HTMLInputElement ? search.value.trim().toLowerCase() : ''
    const matched = imageItems.filter((item) => {
      const haystack = `${item.name} ${item.alt}`.toLowerCase()
      return keyword === '' || haystack.includes(keyword)
    })

    list.textContent = ''
    if (matched.length === 0) {
      const empty = document.createElement('div')
      empty.className = 'rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center text-sm font-bold text-slate-500'
      empty.textContent = imageItems.length === 0 ? '登録済み画像はありません。' : '一致する画像はありません。'
      list.append(empty)
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
      option.addEventListener('click', () => insert(item.src, item.alt))
      grid.append(option)
    }
    list.append(grid)
  }

  const open = () => {
    render()
    panel.classList.remove('hidden')
    if (search instanceof HTMLInputElement) {
      search.focus()
    }
  }

  search?.addEventListener('input', render)
  panel.querySelector('[data-rich-image-close]')?.addEventListener('click', close)
  manualForm?.addEventListener('submit', (event) => {
    event.preventDefault()
    if (manualSrc instanceof HTMLInputElement && manualAlt instanceof HTMLInputElement) {
      insert(manualSrc.value, manualAlt.value)
    }
  })

  return { open, close }
}

const createSelectionMenu = ({ editor, shell, mount, getMode, hideSlashMenu, closeImagePicker }) => {
  const menu = document.createElement('div')
  menu.className = 'absolute z-30 hidden rounded-lg border border-slate-200 bg-white p-2 shadow-xl'
  menu.innerHTML = `
    <div class="flex flex-wrap items-center gap-1" data-selection-format-row>
      ${selectionActions.map((action) => selectionButton(action)).join('')}
    </div>
    <form class="mt-2 hidden grid min-w-72 grid-cols-[minmax(0,1fr)_auto_auto] gap-2 border-t border-slate-200 pt-2" data-selection-link-form>
      <input class="min-h-9 rounded-md border border-slate-300 px-3 py-1.5 text-sm" name="href" autocomplete="off" placeholder="https://example.com">
      <button class="min-h-9 rounded-md bg-blue-600 px-3 py-1.5 text-sm font-bold text-white hover:bg-blue-700" type="submit">設定</button>
      <button class="min-h-9 rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-bold text-slate-700 hover:bg-slate-100" type="button" data-selection-unlink>解除</button>
    </form>
  `
  shell.append(menu)

  const linkForm = menu.querySelector('[data-selection-link-form]')
  const linkInput = linkForm?.querySelector('input[name="href"]')
  const formatRow = menu.querySelector('[data-selection-format-row]')
  let isMouseSelecting = false

  const hide = () => {
    menu.classList.add('hidden')
    linkForm?.classList.add('hidden')
  }

  const updateState = () => {
    menu.querySelectorAll('[data-selection-action]').forEach((item) => {
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

    if (linkInput instanceof HTMLInputElement) {
      linkInput.value = editor.getAttributes('link').href || ''
    }
  }

  const update = () => {
    if (isMouseSelecting) {
      hide()
      return
    }

    if (getMode() !== 'rich_text' || !editor.isEditable || !editor.isFocused) {
      hide()
      return
    }

    const { from, to, empty } = editor.state.selection
    if (empty || from === to || !formatRow) {
      hide()
      return
    }

    hideSlashMenu()
    closeImagePicker()
    updateState()

    const start = editor.view.coordsAtPos(from)
    const end = editor.view.coordsAtPos(to)
    const shellRect = shell.getBoundingClientRect()
    menu.style.visibility = 'hidden'
    menu.classList.remove('hidden')
    const center = (start.left + end.right) / 2 - shellRect.left
    const menuWidth = menu.offsetWidth || 360
    const left = Math.max(12, Math.min(center - menuWidth / 2, shellRect.width - menuWidth - 12))
    const top = Math.max(12, start.top - shellRect.top - menu.offsetHeight - 10)
    menu.style.left = `${left}px`
    menu.style.top = `${top}px`
    menu.style.visibility = ''
  }

  const openLinkForm = () => {
    updateState()
    linkForm?.classList.remove('hidden')
    if (linkInput instanceof HTMLInputElement) {
      linkInput.focus()
      linkInput.select()
    }
  }

  menu.addEventListener('mousedown', (event) => {
    const target = event.target
    if (target instanceof Element && !target.closest('input')) {
      event.preventDefault()
    }
  })
  menu.addEventListener('click', (event) => {
    const target = event.target
    if (!(target instanceof Element)) {
      return
    }

    const item = target.closest('[data-selection-action]')
    if (!(item instanceof HTMLElement)) {
      return
    }

    const actionName = item.dataset.selectionAction || ''
    if (actionName === 'link') {
      openLinkForm()
      return
    }

    const action = selectionActions.find((candidate) => candidate.name === actionName)
    action?.run?.(editor)
    update()
  })
  linkForm?.addEventListener('submit', (event) => {
    event.preventDefault()
    if (!(linkInput instanceof HTMLInputElement)) {
      return
    }

    const href = linkInput.value.trim()
    if (href === '') {
      editor.chain().focus().unsetLink().run()
    } else {
      editor.chain().focus().extendMarkRange('link').setLink({ href }).run()
    }
    hide()
  })
  menu.querySelector('[data-selection-unlink]')?.addEventListener('click', () => {
    editor.chain().focus().unsetLink().run()
    hide()
  })
  mount.addEventListener('mousedown', (event) => {
    if (event.button !== 0 || !(event.target instanceof Element) || !event.target.closest('.ProseMirror')) {
      return
    }

    isMouseSelecting = true
    hide()
  })
  document.addEventListener('mouseup', () => {
    if (!isMouseSelecting) {
      return
    }

    isMouseSelecting = false
    window.setTimeout(update, 0)
  })

  return { hide, update, contains: (target) => menu.contains(target) }
}

const createSlashMenu = ({ editor, shell, getMode, openImagePicker, hideSelectionMenu }) => {
  const menu = document.createElement('div')
  menu.className = 'absolute z-30 hidden w-72 overflow-hidden rounded-lg border border-slate-200 bg-white py-2 shadow-xl'
  menu.setAttribute('role', 'listbox')
  shell.append(menu)

  let state = null
  let selectedIndex = 0
  let filteredItems = []

  const hide = () => {
    menu.classList.add('hidden')
    state = null
    selectedIndex = 0
    filteredItems = []
  }

  const runItem = (item) => {
    if (!state) {
      return
    }

    editor.chain().focus().deleteRange({ from: state.from, to: state.to }).run()
    item.run(editor, { openImagePicker })
    hide()
  }

  const render = () => {
    menu.textContent = ''
    filteredItems.forEach((item, index) => {
      const option = document.createElement('button')
      option.type = 'button'
      option.className = `grid w-full gap-0.5 px-4 py-2 text-left hover:bg-slate-100 ${index === selectedIndex ? 'bg-slate-100' : ''}`
      option.setAttribute('role', 'option')
      option.setAttribute('aria-selected', index === selectedIndex ? 'true' : 'false')
      option.innerHTML = `<span class="text-sm font-bold text-slate-900">${escapeHtml(item.label)}</span><span class="text-xs text-slate-500">${escapeHtml(item.hint)}</span>`
      option.addEventListener('mousedown', (event) => {
        event.preventDefault()
        runItem(item)
      })
      menu.append(option)
    })
  }

  const update = () => {
    if (getMode() !== 'rich_text') {
      hide()
      hideSelectionMenu()
      return
    }

    const { state: editorState, view } = editor
    const { from, empty } = editorState.selection
    if (!empty) {
      hide()
      return
    }

    const $from = editorState.selection.$from
    const blockStart = $from.start()
    const textBeforeCursor = editorState.doc.textBetween(blockStart, from, '\n', '\n')
    const match = textBeforeCursor.match(/^\/([\p{L}\p{N}_-]*)?$/u)
    if (!match) {
      hide()
      return
    }

    const query = (match[1] || '').toLowerCase()
    filteredItems = slashItems.filter((item) => {
      const haystack = [item.label, item.hint, ...item.keywords].join(' ').toLowerCase()
      return query === '' || haystack.includes(query)
    })
    if (filteredItems.length === 0) {
      hide()
      return
    }

    state = { from: from - textBeforeCursor.length, to: from }
    selectedIndex = Math.min(selectedIndex, filteredItems.length - 1)
    render()

    const caret = view.coordsAtPos(from)
    const shellRect = shell.getBoundingClientRect()
    menu.style.left = `${Math.max(12, Math.min(caret.left - shellRect.left, shellRect.width - 300))}px`
    menu.style.top = `${caret.bottom - shellRect.top + 8}px`
    menu.classList.remove('hidden')
  }

  const handleKeyDown = (event) => {
    if (menu.classList.contains('hidden')) {
      return false
    }
    if (event.key === 'ArrowDown') {
      event.preventDefault()
      selectedIndex = (selectedIndex + 1) % filteredItems.length
      render()
      return true
    }
    if (event.key === 'ArrowUp') {
      event.preventDefault()
      selectedIndex = (selectedIndex - 1 + filteredItems.length) % filteredItems.length
      render()
      return true
    }
    if (event.key === 'Enter') {
      event.preventDefault()
      runItem(filteredItems[selectedIndex])
      return true
    }
    if (event.key === 'Escape') {
      event.preventDefault()
      hide()
      return true
    }
    return false
  }

  return { hide, update, handleKeyDown }
}

const appendToolbar = ({ toolbar, editor, openImagePicker }) => {
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
}

const createFormatSwitcher = ({ root, textarea, mount, toolbar, radios, editor, hideSlashMenu, hideSelectionMenu, onModeChanged = () => {} }) => {
  let currentMode = radios.find((radio) => radio.checked)?.value || 'markdown'
  const initialBody = textarea.value
  const drafts = {
    markdown: currentMode === 'markdown' ? initialBody : '',
    html: currentMode === 'html' ? initialBody : '',
    rich_text: currentMode === 'rich_text' ? initialBody : '',
  }

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

  const update = () => {
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
    } else {
      textarea.value = drafts[current] || ''
      hideSlashMenu()
      hideSelectionMenu()
    }
    onModeChanged()
  }

  if (currentMode === 'rich_text') {
    drafts.rich_text = JSON.stringify(editor.getJSON())
  }

  for (const radio of radios) {
    radio.addEventListener('change', update)
  }

  textarea.form?.addEventListener('submit', () => {
    if ((radios.find((radio) => radio.checked)?.value || 'markdown') === 'rich_text') {
      syncBody(editor, textarea)
    } else {
      saveCurrentDraft()
      textarea.value = drafts[currentMode] || ''
    }
  })

  return { getMode: () => currentMode, update }
}

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

    let slashMenu = null
    let selectionMenu = null
    let formatSwitcher = null
    const checkedMode = () => radios.find((radio) => radio.checked)?.value || 'markdown'
    const currentMode = () => formatSwitcher?.getMode() || checkedMode()
    const refreshFloatingMenus = () => {
      slashMenu?.update()
      selectionMenu?.update()
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
          return slashMenu?.handleKeyDown(event) || false
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
      onUpdate: refreshFloatingMenus,
      onSelectionUpdate: refreshFloatingMenus,
      onBlur: ({ event }) => {
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
  })
})
