<script setup lang="ts">
import { reactiveOmit } from '@vueuse/core'
import { ChevronDown } from 'lucide-vue-next'
import type { SelectTriggerProps } from 'reka-ui'
import { SelectIcon, SelectTrigger, useForwardProps } from 'reka-ui'
import type { HTMLAttributes } from 'vue'
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
        `bg-input text-ink-900 hover:border-ink-700 focus:border-brand-500 disabled:bg-muted data-[placeholder]:text-ink-400 flex h-8 w-full items-center justify-between rounded-[2px] border border-(--input-border) px-2.5 text-start text-[13px] transition-colors duration-100 focus:shadow-[inset_0_0_0_1px_var(--color-brand-500)] focus:ring-0 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50 [&>span]:truncate`,
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
