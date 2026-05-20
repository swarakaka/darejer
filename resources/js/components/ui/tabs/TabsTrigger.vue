<script setup lang="ts">
import { reactiveOmit } from '@vueuse/core'
import type { TabsTriggerProps } from 'reka-ui'
import { TabsTrigger, useForwardProps } from 'reka-ui'
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

const props = defineProps<TabsTriggerProps & { class?: HTMLAttributes['class']; hasError?: boolean }>()

const delegatedProps = reactiveOmit(props, 'class', 'hasError')

const forwardedProps = useForwardProps(delegatedProps)
</script>

<template>
  <TabsTrigger
    v-bind="forwardedProps"
    :class="
      cn(
        `hover:text-ink-900 focus-visible:outline-brand-500 data-[state=active]:border-brand-500 data-[state=active]:text-primary relative -mb-px inline-flex h-9 items-center justify-center border-b-2 border-transparent px-3 text-[13px] font-semibold whitespace-nowrap transition-colors duration-100 focus-visible:outline-1 focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50`,
        props.class,
        hasError &&
          `text-danger-600 hover:text-danger-700 data-[state=active]:border-danger-600 data-[state=active]:bg-danger-50 data-[state=active]:text-danger-600 data-[state=active]:shadow-[inset_0_-2px_0_var(--color-danger-600)]`,
      )
    "
  >
    <span class="truncate">
      <slot />
    </span>
  </TabsTrigger>
</template>
