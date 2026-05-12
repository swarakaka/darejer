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

// Safety net for non-Inertia responses to a router visit (e.g. session
// expired mid-navigation). Per-call `useHttp` sites pass the same helper
// to their own `onHttpException` so JSON endpoints also redirect on 401.
router.on('httpException', (event) => {
  if (handleHttpException(event.detail.response)) {
    event.preventDefault()
  }
})

// `app_name` comes from the `darejer.app_name` config, shared on every
// Inertia response. `usePage()` exposes the same global page state the
// rest of the app reads — by the time the title callback fires the
// initial page payload has already hydrated.
function currentAppName(): string {
  const props = usePage().props as { darejer?: { app_name?: string } } | undefined
  return props?.darejer?.app_name ?? 'Darejer'
}

createInertiaApp({
  title: (title) => `${title} - ${currentAppName()}`,

  resolve: (name) =>
    resolvePageComponent(
      `./pages/${name}.vue`,
      import.meta.glob<DefineComponent>('./pages/**/*.vue'),
    ),

  progress: {
    color: 'var(--color-brand-500)',
    showSpinner: false,
  },

  withApp(app) {
    app.use(ZiggyVue)
  },
})
