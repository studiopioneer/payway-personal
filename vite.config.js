import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'

export default defineConfig({
  base: './',
  plugins: [vue()],
  resolve: {
    alias: {
      '@': resolve(__dirname, 'src'),
    },
  },
  build: {
    outDir: 'assets',
    emptyOutDir: true,
    rollupOptions: {
      input: resolve(__dirname, 'src/main.js'),
      output: {
        entryFileNames: 'index-[hash].js',
        chunkFileNames: 'chunks/[name]-[hash].js',
        assetFileNames: 'index-[hash][extname]',
      },
    },
  },
  server: {
    proxy: {
      '/wp-json': {
        target: 'https://payway.store',
        changeOrigin: true,
        secure: true,
      },
    },
  },
})
