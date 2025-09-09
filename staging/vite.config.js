import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import path from 'path';

export default defineConfig({
  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'resources/js'),
    },
  },
  plugins: [
    laravel({
      input: [
        'resources/css/app.css',
        'resources/css/auth.css',
        'resources/css/admin.css',
        'resources/css/land-info.css',
        // Shared CSS files
        'resources/css/shared/variables.css',
        'resources/css/shared/components.css',
        'resources/css/shared/utilities.css',
        // Page-specific CSS files
        'resources/css/pages/facilities.css',
        'resources/css/pages/notifications.css',
        'resources/css/pages/export.css',
        // JavaScript files
        'resources/js/app.js',
        'resources/js/admin.js',
        'resources/js/land-info.js',
        // JavaScript modules (for proper bundling)
        'resources/js/modules/facilities.js',
        'resources/js/modules/notifications.js',
        'resources/js/modules/export.js',
        'resources/js/shared/utils.js',
        'resources/js/shared/api.js',
        'resources/js/shared/validation.js',
        'resources/js/shared/components.js',
        'resources/js/shared/sidebar.js',
        'resources/js/shared/layout.js'
      ],
      refresh: true,
    }),
  ],
  css: {
    preprocessorOptions: {
      css: {
        charset: false
      }
    },
    postcss: {
      plugins: [
        {
          postcssPlugin: 'internal:charset-removal',
          AtRule: {
            charset: (atRule) => {
              if (atRule.name === 'charset') {
                atRule.remove();
              }
            }
          }
        }
      ]
    }
  },
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
        // Shared CSS files
        'resources/css/shared/variables.css',
        'resources/css/shared/components.css',
        'resources/css/shared/utilities.css',
        // Page-specific CSS files
        'resources/css/pages/facilities.css',
        'resources/css/pages/notifications.css',
        'resources/css/pages/export.css',
        // JavaScript files
        'resources/js/app.js',
        'resources/js/admin.js',
        'resources/js/land-info.js',
        // JavaScript modules (for proper bundling)
        'resources/js/modules/facilities.js',
        'resources/js/modules/notifications.js',
        'resources/js/modules/export.js',
        'resources/js/shared/utils.js',
        'resources/js/shared/api.js',
        'resources/js/shared/validation.js',
        'resources/js/shared/components.js',
        'resources/js/shared/sidebar.js',
        'resources/js/shared/layout.js'
      ],
      output: {
        // Ensure proper ES6 module chunking
        manualChunks: {
          'shared-utils': ['resources/js/shared/utils.js'],
          'shared-api': ['resources/js/shared/api.js'],
          'shared-validation': ['resources/js/shared/validation.js'],
          'shared-components': ['resources/js/shared/components.js'],
          'shared-sidebar': ['resources/js/shared/sidebar.js'],
          'shared-layout': ['resources/js/shared/layout.js'],
          'modules': [
            'resources/js/modules/facilities.js',
            'resources/js/modules/notifications.js',
            'resources/js/modules/export.js'
          ]
        }
      }
    }
  },
  test: {
    environment: 'jsdom',
    globals: true,
    setupFiles: ['tests/js/setup.js']
  }
});
