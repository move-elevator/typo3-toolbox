import js from '@eslint/js'

export default [
  js.configs.recommended,
  {
    files: [
      '*.{js,mjs}',
      '**/*.{js,mjs}'
    ],
    languageOptions: {
      globals: {
        console: 'readonly',
        document: 'readonly',
        fetch: 'readonly',
        window: 'readonly'
      }
    },
    rules: {
      'no-undef': 'error',
      'quotes': ['error', 'single'],
      'semi': ['error', 'never']
    }
  },
  {
    ignores: [
      '**/*.min.js',
      'public',
      'var',
      'vendor'
    ]
  }
]
