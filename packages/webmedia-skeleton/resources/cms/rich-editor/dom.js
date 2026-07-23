export const escapeHtml = (value) => value
  .replace(/&/g, '&amp;')
  .replace(/</g, '&lt;')
  .replace(/>/g, '&gt;')
  .replace(/"/g, '&quot;')
  .replace(/'/g, '&#039;')

export const button = (label, action, options = {}) => {
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

export const icon = (path, options = {}) => {
  const label = options.label || ''
  const viewBox = options.viewBox || '0 0 24 24'

  return `
    <svg aria-hidden="true" viewBox="${viewBox}" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      ${path}
    </svg>
    <span class="sr-only">${escapeHtml(label)}</span>
  `
}

export const isLikelyUrl = (value) => /^(https?:\/\/|\/)[^\s]+$/i.test(value.trim())
