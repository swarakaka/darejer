<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

const props = defineProps<{
  class?: HTMLAttributes['class']
}>()
</script>

<template>
  <div :class="cn(`bg-paper-75 flex items-center border-t border-(--card-border) px-4 py-3`, props.class)">
    <slot />
  </div>
</template>
