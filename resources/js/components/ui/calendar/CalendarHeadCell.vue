<script lang="ts" setup>
import { reactiveOmit } from '@vueuse/core'
import type { CalendarHeadCellProps } from 'reka-ui'
import { CalendarHeadCell, useForwardProps } from 'reka-ui'
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

const props = defineProps<CalendarHeadCellProps & { class?: HTMLAttributes['class'] }>()

const delegatedProps = reactiveOmit(props, 'class')

const forwardedProps = useForwardProps(delegatedProps)
</script>

<template>
  <CalendarHeadCell
    :class="cn(`text-muted-foreground w-9 rounded-md text-[0.8rem] font-normal`, props.class)"
    v-bind="forwardedProps"
  >
    <slot />
  </CalendarHeadCell>
</template>
