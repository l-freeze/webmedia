import { selectionActions } from './actions.js'
import { escapeHtml, icon } from './dom.js'

const selectionButton = (action) => `
  <button class="inline-flex h-8 w-8 items-center justify-center rounded-md ${action.classes}" type="button" data-selection-action="${action.name}" title="${escapeHtml(action.label)}" aria-label="${escapeHtml(action.label)}">
    ${icon(action.icon, { label: action.label })}
  </button>
`

export const createSelectionMenu = ({ editor, shell, mount, getMode, hideSlashMenu, closeImagePicker }) => {
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
