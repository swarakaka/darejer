<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

const props = defineProps<{
  class?: HTMLAttributes['class']
}>()
</script>

<template>
  <h3 :class="cn('text-ink-900 text-base leading-tight font-semibold tracking-tight', props.class)">
    <slot />
  </h3>
</template>
