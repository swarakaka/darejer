<script setup lang="ts">
import { computed, watchEffect } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { ConfigProvider } from 'reka-ui'
import AppTopbar from '@/components/darejer/AppTopbar.vue'
import FlashMessage from '@/components/darejer/FlashMessage.vue'
import type { DarejerSharedProps } from '@/types/darejer'

// Mirrors AppLayout's locale → <html lang>/<html dir> sync but skips the
// sidebar entirely — the Home page renders the navigation itself as a
// tile grid, so a parallel sidebar would just be redundant chrome.
const page = usePage<DarejerSharedProps>()
const locale = computed(() => page.props.darejer?.locale ?? 'en')
const direction = computed(() => page.props.darejer?.direction ?? 'ltr')

watchEffect(() => {
  if (typeof document === 'undefined') return
  document.documentElement.lang = locale.value
  document.documentElement.dir = direction.value
})
</script>

<template>
  <ConfigProvider :dir="direction" :locale="locale">
    <div class="flex h-screen flex-col overflow-hidden bg-paper-100 text-ink-900 antialiased">
      <AppTopbar />
      <main class="min-h-0 flex-1 overflow-hidden">
        <slot />
      </main>
      <FlashMessage />
    </div>
  </ConfigProvider>
</template>
