<script setup lang="ts">
import type { SelectTriggerProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { reactiveOmit } from "@vueuse/core"
import { ChevronDown } from "lucide-vue-next"
import { SelectIcon, SelectTrigger, useForwardProps } from "reka-ui"
import { cn } from "@/lib/utils"

const props = defineProps<SelectTriggerProps & { class?: HTMLAttributes["class"] }>()

const delegatedProps = reactiveOmit(props, "class")

const forwardedProps = useForwardProps(delegatedProps)
</script>

<template>
  <SelectTrigger
    v-bind="forwardedProps"
    :class="cn(
      'flex h-8 w-full items-center justify-between rounded-[2px] border border-paper-300 bg-white px-2.5 text-[13px] text-ink-900 text-start transition-colors duration-100 data-[placeholder]:text-ink-400 hover:border-ink-700 focus:border-brand-500 focus:outline-none focus:ring-0 focus:shadow-[inset_0_0_0_1px_var(--color-brand-500)] disabled:cursor-not-allowed disabled:opacity-50 disabled:bg-paper-100 [&>span]:truncate',
      props.class,
    )"
  >
    <slot />
    <SelectIcon as-child>
      <ChevronDown class="w-4 h-4 opacity-50 shrink-0" />
    </SelectIcon>
  </SelectTrigger>
</template>
