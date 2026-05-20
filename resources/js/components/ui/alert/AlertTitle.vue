<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

const props = defineProps<{
  class?: HTMLAttributes['class']
}>()
</script>

<template>
  <h5 :class="cn(`text-ink-900 mb-0.5 text-[13px] leading-tight font-semibold tracking-tight`, props.class)">
    <slot />
  </h5>
</template>
