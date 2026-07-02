import { escapeHtml } from './dom.js'

export const createImagePicker = ({ shell, imageLibrary, onSelect }) => {
  const imageItems = imageLibrary instanceof HTMLElement
    ? Array.from(imageLibrary.querySelectorAll('[data-rich-image-option]')).map((option) => ({
      src: option.dataset.src || '',
      name: option.dataset.name || '',
      alt: option.dataset.alt || '',
      width: option.dataset.width || '',
      height: option.dataset.height || '',
      variant: option.dataset.variant || '元画像',
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
      const haystack = `${item.name} ${item.alt} ${item.variant}`.toLowerCase()
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
      const dimensions = item.width !== '' && item.height !== '' ? `${item.width} x ${item.height}px` : '寸法未取得'
      option.innerHTML = `
        <img class="aspect-[4/3] w-full bg-slate-100 object-cover" src="${escapeHtml(item.src)}" alt="${escapeHtml(item.alt || item.name)}" loading="lazy">
        <span class="block truncate px-2 py-1.5 text-xs font-bold text-slate-700">${escapeHtml(item.name)}</span>
        <span class="block truncate px-2 text-[11px] font-bold text-blue-700">${escapeHtml(item.variant)}</span>
        <span class="block truncate px-2 pb-2 text-[11px] text-slate-500">${escapeHtml(dimensions)}</span>
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
