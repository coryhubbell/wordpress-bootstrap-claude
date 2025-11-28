import { defineConfig, loadEnv } from 'vite'
import react from '@vitejs/plugin-react'
import path from 'path'

// https://vite.dev/config/
export default defineConfig(({ mode }) => {
  // Load env file from parent directory (project root)
  const env = loadEnv(mode, path.resolve(__dirname, '..'), '')

  // Configuration from environment with defaults
  const vitePort = parseInt(env.VITE_PORT || '3000', 10)
  const wordpressPort = env.WORDPRESS_PORT || '8080'
  const hmrHost = env.VITE_HMR_HOST || 'localhost'

  return {
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
      manifest: true, // Generate manifest.json for WordPress integration
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
      host: '0.0.0.0', // Listen on all interfaces for Docker access
      port: vitePort,
      strictPort: true, // Fail if port is already in use
      // Allow access from WordPress dev server
      cors: {
        origin: [
          `http://localhost:${wordpressPort}`,
          `http://127.0.0.1:${wordpressPort}`,
          `http://localhost:${vitePort}`,
          `http://${hmrHost}:${vitePort}`,
        ],
        credentials: true,
      },
      hmr: {
        host: hmrHost,
        protocol: 'ws',
        port: vitePort,
      },
    },

    // Optimize dependencies
    optimizeDeps: {
      include: ['react', 'react-dom', '@monaco-editor/react', 'zustand', '@tanstack/react-query'],
    },
  }
})
