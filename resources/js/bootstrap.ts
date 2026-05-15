import { createInertiaApp, router, usePage } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { ZiggyVue } from 'ziggy-js'
import type { DefineComponent } from 'vue'

// CSS is imported here so Vite records it against the app entry in manifest.json.
// AssetHelper::tags() then emits a <link> for it from the published build output.
import '../css/app.css'

// Apply persisted theme (light/dark/system) before the app mounts so the
// first paint already has the right `dark` class on <html>.
import '@/composables/useTheme'

import { handleHttpException } from '@/lib/handleHttpException'

type PageGlob = Record<string, () => Promise<DefineComponent>>

export interface BootstrapDarejerOptions {
  /**
   * Host-provided Vue page modules. Keys must be relative paths matching the
   * Inertia naming convention (e.g. `./pages/POS/Checkout.vue`). When a host
   * page and a package page share the same name, the host's takes precedence.
   */
  hostPages?: PageGlob
}

/**
 * Boot the Darejer Inertia app. Standalone the package calls this with no
 * options; host apps can extend the page set by passing their own page glob,
 * which is consulted before falling back to the package's pages.
 */
export function bootstrapDarejer(options: BootstrapDarejerOptions = {}): void {
  const packagePages = import.meta.glob<DefineComponent>('./pages/**/*.vue')
  const hostPages = options.hostPages ?? {}

  // Safety net for non-Inertia responses to a router visit (e.g. session
  // expired mid-navigation). Per-call `useHttp` sites pass the same helper
  // to their own `onHttpException` so JSON endpoints also redirect on 401.
  router.on('httpException', (event) => {
    if (handleHttpException(event.detail.response)) {
      event.preventDefault()
    }
  })

  createInertiaApp({
    title: (title) => `${title} - ${currentAppName()}`,

    resolve: (name) => {
      const key = `./pages/${name}.vue`
      if (hostPages[key]) {
        return resolvePageComponent(key, hostPages)
      }
      return resolvePageComponent(key, packagePages)
    },

    progress: {
      // Lower the default 250ms threshold so the bar still appears on fast
      // local navigations (Herd often completes a visit in <250ms).
      delay: 100,
      color: 'var(--color-brand-500)',
      showSpinner: false,
    },

    withApp(app) {
      app.use(ZiggyVue)
    },
  })
}

// `app_name` comes from the `darejer.app_name` config, shared on every
// Inertia response. `usePage()` exposes the same global page state the
// rest of the app reads — by the time the title callback fires the
// initial page payload has already hydrated.
function currentAppName(): string {
  const props = usePage().props as { darejer?: { app_name?: string } } | undefined
  return props?.darejer?.app_name ?? 'Darejer'
}
