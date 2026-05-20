<script setup lang="ts">
import { reactiveOmit } from '@vueuse/core'
import { Check } from 'lucide-vue-next'
import type { SelectItemProps } from 'reka-ui'
import { SelectItem, SelectItemIndicator, SelectItemText, useForwardProps } from 'reka-ui'
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

const props = defineProps<SelectItemProps & { class?: HTMLAttributes['class'] }>()

const delegatedProps = reactiveOmit(props, 'class')

const forwardedProps = useForwardProps(delegatedProps)
</script>

<template>
  <SelectItem
    v-bind="forwardedProps"
    :class="
      cn(
        `text-ink-800 hover:bg-paper-100 focus:bg-paper-100 data-[highlighted]:bg-paper-100 data-[highlighted]:text-ink-900 data-[state=checked]:text-brand-700 relative flex h-8 w-full cursor-pointer items-center ps-8 pe-2.5 text-[13px] transition-colors duration-75 outline-none select-none data-[disabled]:pointer-events-none data-[disabled]:opacity-50 data-[state=checked]:font-semibold`,
        props.class,
      )
    "
  >
    <span class="absolute start-2 flex h-3.5 w-3.5 items-center justify-center">
      <SelectItemIndicator>
        <Check class="text-brand-600 h-3.5 w-3.5" />
      </SelectItemIndicator>
    </span>

    <SelectItemText>
      <slot />
    </SelectItemText>
  </SelectItem>
</template>
