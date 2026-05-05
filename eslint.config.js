import eslint from '@eslint/js'
import eslintConfigPrettier from 'eslint-config-prettier'
import betterTailwindcss from 'eslint-plugin-better-tailwindcss'
import eslintPluginVue from 'eslint-plugin-vue'
import globals from 'globals'
import typescriptEslint from 'typescript-eslint'
import vueParser from 'vue-eslint-parser'

const tailwindSettings = {
  'better-tailwindcss': {
    entryPoint: 'resources/css/app.css',
  },
}

const physicalSpacingClasses = [
  { pattern: '^pl-', message: 'Use ps-* (logical) instead of pl-*.' },
  { pattern: '^pr-', message: 'Use pe-* (logical) instead of pr-*.' },
  { pattern: '^ml-', message: 'Use ms-* (logical) instead of ml-*.' },
  { pattern: '^mr-', message: 'Use me-* (logical) instead of mr-*.' },
  { pattern: '^left-', message: 'Use start-* (logical) instead of left-*.' },
  { pattern: '^right-', message: 'Use end-* (logical) instead of right-*.' },
  { pattern: '^border-l(-|$)', message: 'Use border-s-* (logical) instead of border-l-*.' },
  { pattern: '^border-r(-|$)', message: 'Use border-e-* (logical) instead of border-r-*.' },
  { pattern: '^rounded-l(-|$)', message: 'Use rounded-s-* (logical) instead of rounded-l-*.' },
  { pattern: '^rounded-r(-|$)', message: 'Use rounded-e-* (logical) instead of rounded-r-*.' },
  { pattern: '^text-left$', message: 'Use text-start instead of text-left.' },
  { pattern: '^text-right$', message: 'Use text-end instead of text-right.' },
]

export default typescriptEslint.config(
  {
    ignores: [
      '**/node_modules/**',
      '**/vendor/**',
      '**/public/**',
      'public/build/**',
      'public/vendor/**',
      '**/dist/**',
      '**/*.d.ts',
    ],
  },

  // Base JS / TS rules
  {
    files: ['**/*.{js,mjs,cjs,ts,mts,cts}'],
    extends: [eslint.configs.recommended, ...typescriptEslint.configs.recommended],
    languageOptions: {
      ecmaVersion: 'latest',
      sourceType: 'module',
      globals: { ...globals.browser, ...globals.node },
    },
    rules: {
      '@typescript-eslint/no-unused-vars': [
        'error',
        {
          argsIgnorePattern: '^_',
          varsIgnorePattern: '^_',
          caughtErrorsIgnorePattern: '^_',
          destructuredArrayIgnorePattern: '^_',
        },
      ],
    },
  },

  // Vue SFC rules — Vue parser delegates <script lang="ts"> to TS parser
  {
    files: ['**/*.vue'],
    extends: [
      eslint.configs.recommended,
      ...typescriptEslint.configs.recommended,
      ...eslintPluginVue.configs['flat/recommended'],
    ],
    languageOptions: {
      ecmaVersion: 'latest',
      sourceType: 'module',
      globals: { ...globals.browser },
      parser: vueParser,
      parserOptions: {
        parser: typescriptEslint.parser,
        extraFileExtensions: ['.vue'],
      },
    },
    rules: {
      'vue/multi-word-component-names': 'off',
      'vue/no-v-html': 'off',
      // TypeScript already enforces optional vs required props, so this duplicate
      // check is noise — especially for shadcn-vue passthrough `class` props.
      'vue/require-default-prop': 'off',
      '@typescript-eslint/no-unused-vars': [
        'error',
        {
          argsIgnorePattern: '^_',
          varsIgnorePattern: '^_',
          caughtErrorsIgnorePattern: '^_',
          destructuredArrayIgnorePattern: '^_',
        },
      ],
    },
  },

  // Tailwind v4 — class linting in Vue templates
  {
    files: ['**/*.vue'],
    plugins: {
      'better-tailwindcss': betterTailwindcss,
    },
    settings: tailwindSettings,
    rules: {
      ...betterTailwindcss.configs.recommended.rules,
      'better-tailwindcss/no-unregistered-classes': [
        'error',
        {
          ignore: [
            // Darejer scoped CSS classes (defined in app.css)
            '^darejer-rte',
            // State modifier classes used alongside Tailwind
            '^is-(active|disabled|over-limit)$',
            // Custom drag-handle hooks for vue-draggable-plus
            '^(repeater-)?drag-handle$',
            // Third-party library classes (dhtmlx-gantt, vue-sonner, vue-flow)
            '^gantt[_-]',
            '^toaster$',
            '^vue-flow',
          ],
          detectComponentClasses: true,
        },
      ],
      'better-tailwindcss/no-restricted-classes': ['warn', { restrict: physicalSpacingClasses }],
    },
  },

  // Prettier — must be last to disable formatting rules ESLint would otherwise enforce
  eslintConfigPrettier,
)
