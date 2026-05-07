<script setup lang="ts">
import { computed, watchEffect } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { ConfigProvider } from 'reka-ui'
import FlashMessage from '@/components/darejer/FlashMessage.vue'
import type { DarejerSharedProps } from '@/types/darejer'

// Bare full-screen layout for kiosk-style screens (POS terminal). No sidebar,
// no topbar — the cashier sees only the till. Locale/direction handling is
// preserved so RTL still flips correctly.
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
      <slot />
      <FlashMessage />
    </div>
  </ConfigProvider>
</template>
