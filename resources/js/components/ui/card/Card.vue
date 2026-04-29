<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <div
    :class="
      cn(
        'rounded-[2px] border border-(--card-border) bg-card text-card-foreground shadow-[var(--shadow-blade)]',
        props.class,
      )
    "
  >
    <slot />
  </div>
</template>
