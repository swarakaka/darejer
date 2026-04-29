<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <div :class="cn('flex items-center px-4 py-3 border-t border-(--card-border) bg-paper-75', props.class)">
    <slot />
  </div>
</template>
