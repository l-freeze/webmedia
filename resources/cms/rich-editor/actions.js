export const slashItems = [
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

export const selectionActions = [
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
