import { defineConfig } from 'vite'

export default defineConfig({
  publicDir: false,
  build: {
    emptyOutDir: false,
    lib: {
      entry: 'resources/cms/rich-editor.js',
      formats: ['es'],
      fileName: () => 'cms-rich-editor.js',
    },
    outDir: 'public/assets/js',
    rollupOptions: {
      output: {
        assetFileNames: 'cms-rich-editor.[ext]',
      },
    },
  },
})
