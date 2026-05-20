<script setup lang="ts">
import { reactiveOmit } from '@vueuse/core'
import type { SwitchRootEmits, SwitchRootProps } from 'reka-ui'
import { SwitchRoot, SwitchThumb, useForwardPropsEmits } from 'reka-ui'
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

const props = defineProps<SwitchRootProps & { class?: HTMLAttributes['class'] }>()

const emits = defineEmits<SwitchRootEmits>()

const delegatedProps = reactiveOmit(props, 'class')

const forwarded = useForwardPropsEmits(delegatedProps, emits)
</script>

<template>
  <SwitchRoot
    v-bind="forwarded"
    :class="
      cn(
        `peer border-ink-700 hover:border-ink-900 focus-visible:outline-brand-500 data-[state=checked]:border-brand-500 data-[state=checked]:bg-brand-500 data-[state=checked]:hover:border-brand-600 data-[state=checked]:hover:bg-brand-600 inline-flex h-5 w-10 shrink-0 cursor-pointer items-center rounded-full border-2 bg-transparent transition-colors duration-150 focus-visible:outline-1 focus-visible:outline-offset-1 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50`,
        props.class,
      )
    "
  >
    <SwitchThumb
      :class="
        cn(
          `bg-ink-700 data-[state=checked]:bg-card pointer-events-none block h-2.5 w-2.5 translate-x-1 rounded-full transition-transform duration-150 data-[state=checked]:translate-x-[1.375rem] rtl:-translate-x-1 rtl:data-[state=checked]:-translate-x-[1.375rem]`,
        )
      "
    >
      <slot name="thumb" />
    </SwitchThumb>
  </SwitchRoot>
</template>
