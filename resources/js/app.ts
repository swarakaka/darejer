import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { ZiggyVue } from 'ziggy-js'
import type { DefineComponent } from 'vue'

// CSS is imported here so Vite records it against the app entry in manifest.json.
// AssetHelper::tags() then emits a <link> for it from the published build output.
import '../css/app.css'

createInertiaApp({
    title: (title) => `${title} - Darejer`,

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
