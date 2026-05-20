<script setup lang="ts">
import { reactiveOmit } from '@vueuse/core'
import type { RadioGroupItemProps } from 'reka-ui'
import { RadioGroupIndicator, RadioGroupItem, useForwardProps } from 'reka-ui'
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

const props = defineProps<RadioGroupItemProps & { class?: HTMLAttributes['class'] }>()

const delegatedProps = reactiveOmit(props, 'class')

const forwardedProps = useForwardProps(delegatedProps)
</script>

<template>
  <RadioGroupItem
    v-bind="forwardedProps"
    :class="
      cn(
        `peer bg-input focus-visible:ring-brand-500 data-[state=checked]:border-brand-600 relative aspect-square h-4 w-4 cursor-pointer rounded-full border border-(--input-border) p-0 transition-colors focus:outline-none focus-visible:ring-1 focus-visible:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-50`,
        props.class,
      )
    "
  >
    <RadioGroupIndicator
      class="after:bg-brand-600 pointer-events-none absolute inset-0 after:absolute after:top-1/2 after:left-1/2 after:h-2 after:w-2 after:-translate-x-1/2 after:-translate-y-1/2 after:rounded-full after:content-['']"
    />
  </RadioGroupItem>
</template>
