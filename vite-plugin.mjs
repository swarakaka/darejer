import vue from '@vitejs/plugin-vue'
import path from 'node:path'
import { fileURLToPath } from 'node:url'

const __filename = fileURLToPath(import.meta.url)
const packageRoot = path.dirname(__filename)
const darejerJs = path.resolve(packageRoot, 'resources/js')
const darejerCss = path.resolve(packageRoot, 'resources/css')

/**
 * Darejer Vite plugin.
 *
 * Drops into a host app's vite.config.js next to laravel() and tailwindcss()
 * and wires every piece needed to consume Darejer's frontend source:
 *
 *   - @vitejs/plugin-vue for SFC compilation
 *   - `@` / `@darejer` aliases → vendored darejer/resources/js
 *   - `@host` alias → the host app's resources/js (or a custom path)
 *   - Ensures Vue + Inertia are deduplicated against Darejer's source
 *
 * Host usage:
 *
 *   import darejer from 'darejer/vite'
 *   export default defineConfig({
 *     plugins: [
 *       laravel({ input: ['resources/css/app.css', 'resources/js/app.js'], refresh: true }),
 *       darejer(),
 *       tailwindcss(),
 *     ],
 *   })
 *
 *   // resources/js/app.js
 *   import { bootstrapDarejer } from 'darejer/bootstrap'
 *   const hostPages = import.meta.glob('./pages/**\/*.vue')
 *   bootstrapDarejer({ hostPages })
 */
export default function darejer(options = {}) {
  const hostJs = options.hostJs ?? 'resources/js'

  return [
    vue({
      template: {
        transformAssetUrls: { base: null, includeAbsolute: false },
      },
    }),
    {
      name: 'darejer',
      enforce: 'pre',
      config() {
        const hostRoot = path.resolve(process.cwd(), hostJs)
        return {
          resolve: {
            alias: {
              '@': darejerJs,
              '@darejer': darejerJs,
              '@darejer-css': darejerCss,
              '@host': hostRoot,
            },
            // Vue/Inertia must resolve to a single instance even though
            // some imports come from the vendored package and some from
            // the host's own pages — otherwise reactivity breaks across
            // the boundary.
            dedupe: ['vue', '@inertiajs/vue3'],
          },
        }
      },
    },
  ]
}
