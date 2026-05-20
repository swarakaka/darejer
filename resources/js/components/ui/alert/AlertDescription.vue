<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

const props = defineProps<{
  class?: HTMLAttributes['class']
}>()
</script>

<template>
  <div :class="cn(`text-ink-700 text-[13px] [&_p]:leading-snug`, props.class)">
    <slot />
  </div>
</template>
