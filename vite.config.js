import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
  plugins: [
    laravel({
      input: [
        'resources/css/app.css',
        'resources/css/auth.css',
        'resources/css/admin.css',
        'resources/css/land-info.css',
        'resources/js/app.js',
        'resources/js/admin.js',
        'resources/js/land-info.js'
      ],
      refresh: true,
    }),
  ],
  server: {
    host: '0.0.0.0',
    port: 5173,
    hmr: {
      host: 'localhost',
    },
  },
  build: {
    outDir: 'public/build',
    manifest: true,
    rollupOptions: {
      input: [
        'resources/css/app.css',
        'resources/css/auth.css',
        'resources/css/admin.css',
        'resources/css/land-info.css',
        'resources/js/app.js',
        'resources/js/admin.js',
        'resources/js/land-info.js'
      ]
    }
  },
  test: {
    environment: 'jsdom',
    globals: true,
    setupFiles: ['tests/js/setup.js']
  }
});
