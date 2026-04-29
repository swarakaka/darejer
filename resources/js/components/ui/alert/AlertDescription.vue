<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <div :class="cn('text-[13px] text-ink-700 [&_p]:leading-snug', props.class)">
    <slot />
  </div>
</template>
