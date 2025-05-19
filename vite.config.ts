import { defineConfig } from 'vite'
import preact from '@preact/preset-vite'

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [preact()],
  root: './assets',
  base: '/assets/',
  build: {
    manifest: true,
    assetsDir: '',
    outDir: '../public/assets/',
    rollupOptions: {
      output: {
        manualChunks: undefined
      },
      input: {
        'front.ts': './assets/front.ts',
        'admin.ts': './assets/admin.ts',
      }
    }
  }
})