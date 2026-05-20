<script setup lang="ts">
import { usePage } from '@inertiajs/vue3'
import { ConfigProvider } from 'reka-ui'
import { computed } from 'vue'
import type { DarejerSharedProps } from '@/types/darejer'

const page = usePage<DarejerSharedProps>()
const locale = computed(() => page.props.darejer?.locale ?? 'en')
const direction = computed(() => page.props.darejer?.direction ?? 'ltr')
</script>

<template>
  <ConfigProvider :dir="direction" :locale="locale">
    <div
      class="bg-paper-100 text-ink-900 flex min-h-screen items-center justify-center px-6 py-12 antialiased sm:px-10 lg:py-16"
    >
      <div class="bg-card w-full max-w-sm shadow-md">
        <slot />
      </div>
    </div>
  </ConfigProvider>
</template>
