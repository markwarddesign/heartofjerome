import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// In dev, proxy the PHP API + uploaded files to a local `php -S` server
// (run from the repo root so /api/*.php and /uploads/* resolve).
export default defineConfig({
  plugins: [react()],
  server: {
    port: 5173,
    proxy: {
      '/api': { target: 'http://127.0.0.1:8790', changeOrigin: true },
      '/uploads': { target: 'http://127.0.0.1:8790', changeOrigin: true },
    },
  },
  build: {
    // Build to a repo-level web root that both Valet (local) and Hostinger serve.
    outDir: '../public_html',
    emptyOutDir: true,
  },
})
