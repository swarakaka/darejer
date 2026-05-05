<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

const props = defineProps<{
  class?: HTMLAttributes['class']
}>()
</script>

<template>
  <div
    :class="
      cn(`flex flex-col gap-y-1 border-b border-(--card-border) px-4 pt-3.5 pb-3`, props.class)
    "
  >
    <slot />
  </div>
</template>
