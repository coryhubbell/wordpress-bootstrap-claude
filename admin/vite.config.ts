import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react-swc'
import path from 'path'

// https://vite.dev/config/
export default defineConfig({
  plugins: [react()],

  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
      '@components': path.resolve(__dirname, './src/components'),
      '@services': path.resolve(__dirname, './src/services'),
      '@features': path.resolve(__dirname, './src/features'),
      '@hooks': path.resolve(__dirname, './src/hooks'),
      '@types': path.resolve(__dirname, './src/types'),
      '@utils': path.resolve(__dirname, './src/utils'),
    },
  },

  build: {
    outDir: 'dist',
    emptyOutDir: true,
    rollupOptions: {
      input: {
        main: path.resolve(__dirname, 'index.html'),
      },
      output: {
        entryFileNames: 'assets/[name].[hash].js',
        chunkFileNames: 'assets/[name].[hash].js',
        assetFileNames: 'assets/[name].[hash].[ext]',
      },
    },
    // Generate source maps for debugging
    sourcemap: true,
    // Optimize chunk size
    chunkSizeWarningLimit: 1000,
  },

  server: {
    host: '0.0.0.0', // Listen on all interfaces
    port: 3000,
    strictPort: true,
    // Allow access from WordPress dev server
    cors: {
      origin: ['http://localhost:8080', 'http://localhost:3000'],
      credentials: true,
    },
    hmr: {
      host: 'localhost',
      protocol: 'ws',
      port: 3000,
    },
  },

  // Optimize dependencies
  optimizeDeps: {
    include: ['react', 'react-dom', '@monaco-editor/react', 'zustand', '@tanstack/react-query'],
  },
})
