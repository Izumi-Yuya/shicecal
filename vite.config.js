import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import path from 'path';
import { createHash } from 'crypto';

export default defineConfig({
  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'resources/js'),
    },
    // Optimize module resolution
    extensions: ['.js', '.ts', '.jsx', '.tsx', '.json', '.vue'],
    // Dedupe dependencies
    dedupe: ['lodash', 'axios'],
  },
  // Enable dependency pre-bundling optimization
  optimizeDeps: {
    include: ['lodash', 'axios'],
    exclude: [],
    // Force optimization of specific dependencies
    force: false,
    // ESBuild options for dependency optimization
    esbuildOptions: {
      target: 'es2020',
      supported: {
        'top-level-await': true
      }
    }
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
    },
    // Enable CSS minification and optimization
    devSourcemap: true,
    // CSS optimization options
    minify: true
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
    // Enable minification and compression
    minify: 'terser',
    terserOptions: {
      compress: {
        drop_console: true,
        drop_debugger: true,
        pure_funcs: ['console.log', 'console.info', 'console.debug'],
        passes: 3,
        unsafe: true,
        unsafe_comps: true,
        unsafe_math: true,
        unsafe_proto: true,
        unsafe_regexp: true,
        conditionals: true,
        dead_code: true,
        evaluate: true,
        if_return: true,
        join_vars: true,
        reduce_vars: true,
        sequences: true,
        side_effects: false,
        switches: true,
        top_retain: false,
        unused: true,
      },
      mangle: {
        safari10: true,
        toplevel: true,
        properties: {
          regex: /^_/
        }
      },
      format: {
        comments: false,
        ascii_only: true,
      },
    },
    // Enable asset versioning for cache busting
    assetsDir: 'assets',
    // Optimize chunk size
    chunkSizeWarningLimit: 1000,
    // Enable source maps for production debugging (optional)
    sourcemap: false,
    // Enable CSS code splitting
    cssCodeSplit: true,
    // Asset inlining threshold (files smaller than this will be inlined as base64)
    assetsInlineLimit: 4096,
    // Enable compression
    reportCompressedSize: true,
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
        // Ensure proper ES6 module chunking with optimized file names
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
        },
        // Optimize file naming for better caching
        entryFileNames: (chunkInfo) => {
          const hash = createHash('md5').update(chunkInfo.name).digest('hex').substring(0, 8);
          return `js/[name]-${hash}.[hash].js`;
        },
        chunkFileNames: (chunkInfo) => {
          const hash = createHash('md5').update(chunkInfo.name).digest('hex').substring(0, 8);
          return `js/[name]-${hash}.[hash].js`;
        },
        assetFileNames: (assetInfo) => {
          const info = assetInfo.name.split('.');
          const ext = info[info.length - 1];
          if (/\.(css)$/.test(assetInfo.name)) {
            return `css/[name].[hash].${ext}`;
          }
          if (/\.(png|jpe?g|svg|gif|tiff|bmp|ico)$/i.test(assetInfo.name)) {
            return `images/[name].[hash].${ext}`;
          }
          if (/\.(woff2?|eot|ttf|otf)$/i.test(assetInfo.name)) {
            return `fonts/[name].[hash].${ext}`;
          }
          return `assets/[name].[hash].${ext}`;
        },
        // Enable tree shaking
        preserveModules: false,
        // Optimize for modern browsers
        format: 'es',
        // Enable compression hints
        compact: true,
      },
      // Enable tree shaking
      treeshake: {
        moduleSideEffects: false,
        propertyReadSideEffects: false,
        tryCatchDeoptimization: false,
        unknownGlobalSideEffects: false,
      },
      // External dependencies (don't bundle these)
      external: [],
      // Plugin optimizations
      plugins: []
    }
  },
  test: {
    environment: 'jsdom',
    globals: true,
    setupFiles: ['tests/js/setup.js']
  }
});
