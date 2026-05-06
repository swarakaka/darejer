<script setup lang="ts">
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { ConfigProvider } from 'reka-ui'
import type { DarejerSharedProps } from '@/types/darejer'

const page = usePage<DarejerSharedProps>()
const locale = computed(() => page.props.darejer?.locale ?? 'en')
const direction = computed(() => page.props.darejer?.direction ?? 'ltr')
</script>

<template>
  <ConfigProvider :dir="direction" :locale="locale">
    <div
      class="flex min-h-screen items-center justify-center bg-paper-100 px-6 py-12 text-ink-900 antialiased sm:px-10 lg:py-16"
    >
      <div class="w-full max-w-sm bg-card shadow-md">
        <slot />
      </div>
    </div>
  </ConfigProvider>
</template>
