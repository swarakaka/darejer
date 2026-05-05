<script setup lang="ts">
import type { SelectItemProps } from 'reka-ui'
import type { HTMLAttributes } from 'vue'
import { reactiveOmit } from '@vueuse/core'
import { Check } from 'lucide-vue-next'
import { SelectItem, SelectItemIndicator, SelectItemText, useForwardProps } from 'reka-ui'
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
        `relative flex h-8 w-full cursor-pointer items-center ps-8 pe-2.5 text-[13px] text-ink-800 transition-colors duration-75 outline-none select-none hover:bg-paper-100 focus:bg-paper-100 data-[disabled]:pointer-events-none data-[disabled]:opacity-50 data-[highlighted]:bg-paper-100 data-[highlighted]:text-ink-900 data-[state=checked]:font-semibold data-[state=checked]:text-brand-700`,
        props.class,
      )
    "
  >
    <span class="absolute start-2 flex h-3.5 w-3.5 items-center justify-center">
      <SelectItemIndicator>
        <Check class="h-3.5 w-3.5 text-brand-600" />
      </SelectItemIndicator>
    </span>

    <SelectItemText>
      <slot />
    </SelectItemText>
  </SelectItem>
</template>
