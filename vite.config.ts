import { defineConfig } from 'vite'
import inertia from '@inertiajs/vite'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'
import { resolve } from 'path'

export default defineConfig({
  // All assets are served from /vendor/darejer/ after `vendor:publish` copies them
  // into the host app's public/vendor/darejer directory. `base` + `renderBuiltUrl`
  // make Vite embed that prefix into chunk loaders and modulepreload links, so
  // dynamic imports resolve to /vendor/darejer/assets/… instead of /assets/… .
  // `renderBuiltUrl` is needed because rolldown-vite does not yet apply `base`
  // to the `__vite__mapDeps` preload array.
  base: '/vendor/darejer/',
  experimental: {
    renderBuiltUrl(filename) {
      return { relative: `/vendor/darejer/${filename}` }
    },
  },
  plugins: [
    inertia({
      ssr: false,
    }),
    vue(),
    tailwindcss(),
  ],
    resolve: {
        alias: {
            '@': resolve(__dirname, 'resources/js'),
            '@darejer': resolve(__dirname, 'resources/js'),
        },
    },
    build: {
        outDir: 'public/build',
        emptyOutDir: true,
        manifest: 'manifest.json',
        rollupOptions: {
            input: {
                app: resolve(__dirname, 'resources/js/app.ts'),
            },
            output: {
                manualChunks(id) {
                    if (id.includes('node_modules/vue/') || id.includes('node_modules/@inertiajs/vue3/')) {
                        return 'vendor'
                    }
                },
            },
        },
    },
})
