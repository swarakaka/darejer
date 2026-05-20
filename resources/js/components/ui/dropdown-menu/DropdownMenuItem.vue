<script setup lang="ts">
import { reactiveOmit } from '@vueuse/core'
import type { DropdownMenuItemProps } from 'reka-ui'
import { DropdownMenuItem, useForwardProps } from 'reka-ui'
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

const props = defineProps<DropdownMenuItemProps & { class?: HTMLAttributes['class']; inset?: boolean }>()

const delegatedProps = reactiveOmit(props, 'class')

const forwardedProps = useForwardProps(delegatedProps)
</script>

<template>
  <DropdownMenuItem
    v-bind="forwardedProps"
    :class="
      cn(
        `text-ink-800 hover:bg-paper-100 focus:bg-paper-100 focus:text-ink-900 relative flex cursor-pointer items-center gap-2 rounded-[2px] px-2.5 py-1.5 text-[13px] transition-colors duration-75 outline-none select-none data-[disabled]:pointer-events-none data-[disabled]:opacity-50 [&>svg]:size-3.5 [&>svg]:shrink-0`,
        inset && 'ps-8',
        props.class,
      )
    "
  >
    <slot />
  </DropdownMenuItem>
</template>
