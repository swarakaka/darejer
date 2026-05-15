<script setup lang="ts">
import { computed, watchEffect } from 'vue'
import { usePage, Link } from '@inertiajs/vue3'
import { ConfigProvider } from 'reka-ui'
import FlashMessage from '@/components/darejer/FlashMessage.vue'
import type { DarejerSharedProps } from '@/types/darejer'

// Bare layout for kiosk/full-screen workflows. No sidebar, no topbar — just
// a sticky header with logo (left), page title (center), and a teleport
// target for page-supplied action buttons (right).
//
// Title resolves from `usePage().props.title` so a controller can drive it
// from PHP. Pages that want richer header content render it into the page
// body or replace the header by extending this layout in their host app.
//
// Action buttons: pages teleport their buttons into `#minimal-layout-actions`.
const page = usePage<DarejerSharedProps & { title?: string }>()
const locale = computed(() => page.props.darejer?.locale ?? 'en')
const direction = computed(() => page.props.darejer?.direction ?? 'ltr')
const appName = computed(() => page.props.darejer?.app_name ?? 'Darejer')
const pageTitle = computed(() => page.props.title ?? '')
const homeUrl = computed(() => {
  try {
    return route('darejer.home').toString()
  } catch {
    return '/'
  }
})

watchEffect(() => {
  if (typeof document === 'undefined') return
  document.documentElement.lang = locale.value
  document.documentElement.dir = direction.value
})
</script>

<template>
  <ConfigProvider :dir="direction" :locale="locale">
    <div class="flex h-screen flex-col overflow-hidden bg-paper-100 text-ink-900 antialiased">
      <header
        class="sticky top-0 z-20 flex h-(--topbar-height) shrink-0 items-center gap-2 border-b border-(--topbar-border) bg-(--topbar-bg) px-3 text-(--topbar-foreground) print:hidden"
      >
        <Link
          :href="homeUrl"
          class="flex h-full items-center pe-3 text-[15px] font-normal tracking-tight text-white transition-colors select-none hover:bg-white/[0.12]"
          :aria-label="appName"
        >
          {{ appName }}
        </Link>

        <div class="flex flex-1 items-center justify-center text-[15px] font-medium text-white">
          <span v-if="pageTitle">{{ pageTitle }}</span>
        </div>

        <div id="minimal-layout-actions" class="flex items-center gap-1" />
      </header>

      <main class="min-h-0 flex-1 overflow-hidden">
        <slot />
      </main>

      <FlashMessage />
    </div>
  </ConfigProvider>
</template>
