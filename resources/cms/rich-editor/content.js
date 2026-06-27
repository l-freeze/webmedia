export const emptyDoc = () => ({
  type: 'doc',
  content: [{ type: 'paragraph' }],
})

export const parseContent = (value) => {
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

export const syncBody = (editor, textarea) => {
  textarea.value = JSON.stringify(editor.getJSON())
}

export const richTextToMarkdown = (node) => {
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
