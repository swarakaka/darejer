<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

const props = defineProps<{
  class?: HTMLAttributes['class']
}>()
</script>

<template>
  <th
    :class="
      cn(
        `h-9 border-b border-paper-200 bg-paper-75 px-3 text-start align-middle text-[11px] font-semibold tracking-[0.06em] text-ink-600 uppercase [&:has([role=checkbox])]:pe-0`,
        props.class,
      )
    "
  >
    <slot />
  </th>
</template>
