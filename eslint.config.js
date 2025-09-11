import js from '@eslint/js';

export default [
  js.configs.recommended,
  {
    languageOptions: {
      ecmaVersion: 2022,
      sourceType: 'module',
      globals: {
        window: 'readonly',
        document: 'readonly',
        console: 'readonly',
        fetch: 'readonly',
        FormData: 'readonly',
        URLSearchParams: 'readonly',
        localStorage: 'readonly',
        sessionStorage: 'readonly',
        setTimeout: 'readonly',
        clearTimeout: 'readonly',
        setInterval: 'readonly',
        clearInterval: 'readonly',
        requestAnimationFrame: 'readonly',
        cancelAnimationFrame: 'readonly',
        Event: 'readonly',
        CustomEvent: 'readonly',
        DOMContentLoaded: 'readonly',
        // Laravel/Blade globals
        csrf_token: 'readonly',
        route: 'readonly',
        // Test globals
        describe: 'readonly',
        it: 'readonly',
        test: 'readonly',
        expect: 'readonly',
        beforeEach: 'readonly',
        afterEach: 'readonly',
        beforeAll: 'readonly',
        afterAll: 'readonly',
        vi: 'readonly',
        // Performance API
        performance: 'readonly',
        // Global object
        global: 'readonly',
        // Browser APIs
        bootstrap: 'readonly',
        alert: 'readonly',
        confirm: 'readonly',
        prompt: 'readonly',
        location: 'readonly',
        MutationObserver: 'readonly',
        Node: 'readonly',
        URL: 'readonly',
        // HTTP methods (if using global functions)
        get: 'readonly',
        post: 'readonly'
      }
    },
    rules: {
      // Error prevention
      'no-unused-vars': ['error', {
        argsIgnorePattern: '^_',
        varsIgnorePattern: '^_'
      }],
      'no-undef': 'error',
      'no-console': 'warn',
      'no-debugger': 'error',
      'no-alert': 'warn',

      // Code quality
      'prefer-const': 'error',
      'no-var': 'error',
      'eqeqeq': ['error', 'always'],
      'curly': ['warn', 'all'],
      'brace-style': ['error', '1tbs'],

      // ES6+ features
      'arrow-spacing': 'error',
      'template-curly-spacing': 'error',
      'object-shorthand': 'error',
      'prefer-template': 'error',

      // Formatting (more lenient for existing code)
      'indent': ['warn', 4],
      'quotes': ['warn', 'single'],
      'semi': ['warn', 'always'],
      'comma-dangle': ['warn', 'never'],
      'no-trailing-spaces': 'warn',
      'eol-last': 'warn'
    },
    files: ['resources/js/**/*.js', 'tests/js/**/*.js']
  },
  {
    // Test-specific configuration
    files: ['tests/js/**/*.js'],
    rules: {
      'no-console': 'off' // Allow console in tests
    }
  }
];