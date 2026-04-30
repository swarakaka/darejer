<script setup lang="ts">
import type { TabsTriggerProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { reactiveOmit } from "@vueuse/core"
import { TabsTrigger, useForwardProps } from "reka-ui"
import { cn } from "@/lib/utils"

const props = defineProps<TabsTriggerProps & { class?: HTMLAttributes["class"]; hasError?: boolean }>()

const delegatedProps = reactiveOmit(props, "class", "hasError")

const forwardedProps = useForwardProps(delegatedProps)
</script>

<template>
  <TabsTrigger
    v-bind="forwardedProps"
    :class="cn(
      'relative inline-flex items-center justify-center whitespace-nowrap px-3 h-9 -mb-px text-[13px] font-semibold transition-colors duration-100 border-b-2 border-transparent focus-visible:outline-none focus-visible:outline-1 focus-visible:outline-brand-500 disabled:pointer-events-none disabled:opacity-50 hover:text-ink-900 data-[state=active]:text-brand-700 data-[state=active]:border-brand-500',
      props.class,
      hasError && 'text-danger-600 hover:text-danger-700 data-[state=active]:text-danger-600 data-[state=active]:border-danger-600 data-[state=active]:bg-danger-50 data-[state=active]:shadow-[inset_0_-2px_0_var(--color-danger-600)]',
    )"
  >
    <span class="truncate">
      <slot />
    </span>
  </TabsTrigger>
</template>
