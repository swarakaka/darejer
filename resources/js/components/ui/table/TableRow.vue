<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

const props = defineProps<{
  class?: HTMLAttributes['class']
}>()
</script>

<template>
  <tr
    :class="
      cn(
        `border-paper-150 hover:bg-brand-50 data-[state=selected]:border-brand-100 data-[state=selected]:bg-brand-50 border-b transition-colors duration-75`,
        props.class,
      )
    "
  >
    <slot />
  </tr>
</template>
