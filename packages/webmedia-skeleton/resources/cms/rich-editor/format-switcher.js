import { parseContent, richTextToMarkdown, syncBody } from './content.js'

export const createFormatSwitcher = ({ root, textarea, mount, toolbar, radios, editor, hideSlashMenu, hideSelectionMenu, onModeChanged = () => {} }) => {
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
