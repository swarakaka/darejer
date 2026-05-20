<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

const props = defineProps<{
  class?: HTMLAttributes['class']
}>()
</script>

<template>
  <td :class="cn(`text-ink-800 px-3 py-2 align-middle text-[13px] [&:has([role=checkbox])]:pe-0`, props.class)">
    <slot />
  </td>
</template>
