<script setup lang="ts">
import type { CheckboxRootEmits, CheckboxRootProps } from 'reka-ui'
import type { HTMLAttributes } from 'vue'
import { reactiveOmit } from '@vueuse/core'
import { Check } from 'lucide-vue-next'
import { CheckboxIndicator, CheckboxRoot, useForwardPropsEmits } from 'reka-ui'
import { cn } from '@/lib/utils'

const props = defineProps<CheckboxRootProps & { class?: HTMLAttributes['class'] }>()
const emits = defineEmits<CheckboxRootEmits>()

const delegatedProps = reactiveOmit(props, 'class')

const forwarded = useForwardPropsEmits(delegatedProps, emits)
</script>

<template>
  <CheckboxRoot
    v-bind="forwarded"
    :class="
      cn(
        `peer grid h-4 w-4 shrink-0 cursor-pointer place-content-center rounded-[2px] border border-ink-500 bg-input transition-colors hover:border-ink-700 focus-visible:outline-1 focus-visible:outline-offset-1 focus-visible:outline-brand-500 focus-visible:outline-none disabled:cursor-not-allowed disabled:bg-muted disabled:opacity-50 data-[state=checked]:border-brand-500 data-[state=checked]:bg-brand-500 data-[state=checked]:text-white`,
        props.class,
      )
    "
  >
    <CheckboxIndicator class="grid place-content-center text-current">
      <slot>
        <Check class="h-3 w-3 stroke-[3]" />
      </slot>
    </CheckboxIndicator>
  </CheckboxRoot>
</template>
