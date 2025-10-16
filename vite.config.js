import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import path from 'path';
import { createHash } from 'crypto';

export default defineConfig({
  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'resources/js'),
    },
    extensions: ['.js', '.ts', '.jsx', '.tsx', '.json', '.vue'],
    dedupe: ['lodash', 'axios'],
  },

  optimizeDeps: {
    include: ['lodash', 'axios'],
    exclude: [],
    force: false,
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
        // 統合されたメインファイル
        'resources/css/app.css',
        'resources/css/app-unified.css',
        'resources/css/document-management-unified.css',
        'resources/css/contract-document-management.css',
        'resources/js/app.js',
        'resources/js/app-unified.js',



        // 個別に必要なファイル
        'resources/css/auth.css',
        'resources/css/admin.css',
        'resources/css/land-info-final.css',
        'resources/css/water-equipment.css',
        'resources/css/gas-equipment.css',
        'resources/css/elevator-equipment.css',
        'resources/css/detail-table-clean.css',

        // ページ固有CSS
        'resources/css/pages/facilities.css',
        'resources/css/pages/notifications.css',
        'resources/css/pages/export.css',
        'resources/css/pages/lifeline-equipment.css',
        'resources/css/pages/lifeline-equipment-gas.css',
        'resources/css/pages/lifeline-equipment/variables.css',
        'resources/css/pages/lifeline-equipment/base.css',
        'resources/css/pages/lifeline-equipment/navigation.css',

        // 共有CSS（統合されていないもの）
        'resources/css/shared/components.css',
        'resources/css/shared/utilities.css',
        'resources/css/shared/variables.css',
        'resources/css/layout.css',
        'resources/css/pages.css',

        // 個別JavaScript
        'resources/js/admin.js',
        'resources/js/land-info-final.js',

        // 統合されていない必要なモジュール
        'resources/js/modules/notifications.js',
        'resources/js/modules/export.js',
        'resources/js/modules/facility-view-toggle.js',
        'resources/js/modules/lifeline-equipment.js',
        'resources/js/modules/lifeline-modal-manager.js',
        'resources/js/modules/facility-form-layout.js',
        'resources/js/modules/detail-card-controller.js',
        'resources/js/modules/facilities.js',
        'resources/js/shared/validation.js',
        'resources/js/shared/components.js',
        'resources/js/shared/sidebar.js',
        'resources/js/shared/layout.js',
        'resources/js/shared/utils.js',
        'resources/js/shared/api.js'
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
    devSourcemap: true,
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
    minify: 'terser',
    terserOptions: {
      compress: {
        drop_console: false, // 開発中はconsole.logを保持
        drop_debugger: true,
        pure_funcs: [], // 開発中はconsole関数を削除しない
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
    assetsDir: 'assets',
    chunkSizeWarningLimit: 1000,
    sourcemap: false,
    cssCodeSplit: true,
    assetsInlineLimit: 4096,
    reportCompressedSize: true,
    rollupOptions: {
      output: {
        manualChunks: {
          'app-unified': ['resources/js/app-unified.js'],
          'shared-modules': [
            'resources/js/shared/utils.js',
            'resources/js/shared/api.js',
            'resources/js/shared/validation.js',
            'resources/js/shared/components.js',
            'resources/js/shared/sidebar.js',
            'resources/js/shared/layout.js'
          ],
          'feature-modules': [
            'resources/js/modules/notifications.js',
            'resources/js/modules/export.js',
            'resources/js/modules/facility-view-toggle.js',
            'resources/js/modules/comment-manager.js',
            'resources/js/modules/comment-ui.js',
            'resources/js/modules/lifeline-equipment.js',
            'resources/js/modules/facility-form-layout.js',
            'resources/js/modules/detail-card-controller.js',
            'resources/js/modules/facilities.js'
          ]
        },
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
        preserveModules: false,
        format: 'es',
        compact: true,
      },
      treeshake: {
        moduleSideEffects: false,
        propertyReadSideEffects: false,
        tryCatchDeoptimization: false,
        unknownGlobalSideEffects: false,
      },
      external: [],
      plugins: []
    }
  },

  test: {
    environment: 'jsdom',
    globals: true,
    setupFiles: ['tests/js/setup.js']
  }
});