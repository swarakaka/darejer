<script setup lang="ts">
import { usePage } from '@inertiajs/vue3'
import { ConfigProvider } from 'reka-ui'
import { computed, watchEffect } from 'vue'
import AppSidebar from '@/components/darejer/AppSidebar.vue'
import AppTopbar from '@/components/darejer/AppTopbar.vue'
import FlashMessage from '@/components/darejer/FlashMessage.vue'
import type { DarejerSharedProps } from '@/types/darejer'

// Sync `<html lang>` + `<html dir>` from Darejer's Inertia shared props so the
// whole page (including Tailwind logical utilities `ps-*`, `end-*`, etc.)
// flips orientation when an RTL locale is selected.
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
    <div class="bg-paper-100 text-ink-900 flex h-screen flex-col overflow-hidden antialiased">
      <AppTopbar />
      <div class="flex min-h-0 flex-1 overflow-hidden">
        <AppSidebar />
        <main class="min-h-0 min-w-0 flex-1 overflow-hidden">
          <slot />
        </main>
      </div>
      <FlashMessage />
    </div>
  </ConfigProvider>
</template>
