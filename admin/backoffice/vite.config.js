import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig({
  plugins: [react()],
  base: './',
  build: {
    outDir: 'build',
    assetsDir: 'assets',
    rollupOptions: {
      external: ['@lobehub/ui', 'antd', 'rc-util', 'rc-motion', '@ant-design/icons', 'react-layout-kit'],
      output: {
        entryFileNames: 'assets/index.js',
        chunkFileNames: 'assets/[name]-[hash:8].js',
        assetFileNames: (info) => {
          // CSS entry point garde un nom fixe pour le wp_enqueue_style
          if (info.name === 'index.css') return 'assets/index.css'
          return 'assets/[name]-[hash:8].[ext]'
        },
      },
    },
  },
})
