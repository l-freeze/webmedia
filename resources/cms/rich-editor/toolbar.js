import { button } from './dom.js'

export const appendToolbar = ({ toolbar, editor, openImagePicker }) => {
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
