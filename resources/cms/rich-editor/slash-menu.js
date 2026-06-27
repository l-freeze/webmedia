import { slashItems } from './actions.js'
import { escapeHtml } from './dom.js'

export const createSlashMenu = ({ editor, shell, getMode, openImagePicker, hideSelectionMenu }) => {
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
