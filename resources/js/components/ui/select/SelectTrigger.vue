<script setup lang="ts">
import type { SelectTriggerProps } from 'reka-ui'
import type { HTMLAttributes } from 'vue'
import { reactiveOmit } from '@vueuse/core'
import { ChevronDown } from 'lucide-vue-next'
import { SelectIcon, SelectTrigger, useForwardProps } from 'reka-ui'
import { cn } from '@/lib/utils'

const props = defineProps<SelectTriggerProps & { class?: HTMLAttributes['class'] }>()

const delegatedProps = reactiveOmit(props, 'class')

const forwardedProps = useForwardProps(delegatedProps)
</script>

<template>
  <SelectTrigger
    v-bind="forwardedProps"
    :class="
      cn(
        `flex h-8 w-full items-center justify-between rounded-[2px] border border-(--input-border) bg-input px-2.5 text-start text-[13px] text-ink-900 transition-colors duration-100 hover:border-ink-700 focus:border-brand-500 focus:shadow-[inset_0_0_0_1px_var(--color-brand-500)] focus:ring-0 focus:outline-none disabled:cursor-not-allowed disabled:bg-muted disabled:opacity-50 data-[placeholder]:text-ink-400 [&>span]:truncate`,
        props.class,
      )
    "
  >
    <slot />
    <SelectIcon as-child>
      <ChevronDown class="h-4 w-4 shrink-0 opacity-50" />
    </SelectIcon>
  </SelectTrigger>
</template>
