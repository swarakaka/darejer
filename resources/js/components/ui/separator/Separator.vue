<script setup lang="ts">
import { reactiveOmit } from '@vueuse/core'
import type { SeparatorProps } from 'reka-ui'
import { Separator } from 'reka-ui'
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

const props = withDefaults(defineProps<SeparatorProps & { class?: HTMLAttributes['class'] }>(), {
  orientation: 'horizontal',
  decorative: true,
})

const delegatedProps = reactiveOmit(props, 'class')
</script>

<template>
  <Separator
    v-bind="delegatedProps"
    :class="cn('bg-border shrink-0', props.orientation === 'horizontal' ? 'h-px w-full' : 'h-full w-px', props.class)"
  />
</template>
