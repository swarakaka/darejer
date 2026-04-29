<script setup lang="ts">
import type { SwitchRootEmits, SwitchRootProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { reactiveOmit } from "@vueuse/core"
import {
  SwitchRoot,
  SwitchThumb,
  useForwardPropsEmits,
} from "reka-ui"
import { cn } from "@/lib/utils"

const props = defineProps<SwitchRootProps & { class?: HTMLAttributes["class"] }>()

const emits = defineEmits<SwitchRootEmits>()

const delegatedProps = reactiveOmit(props, "class")

const forwarded = useForwardPropsEmits(delegatedProps, emits)
</script>

<template>
  <SwitchRoot
    v-bind="forwarded"
    :class="cn(
      'peer inline-flex h-5 w-10 shrink-0 cursor-pointer items-center rounded-full border-2 border-ink-700 bg-transparent transition-colors duration-150 hover:border-ink-900 data-[state=checked]:bg-brand-500 data-[state=checked]:border-brand-500 data-[state=checked]:hover:bg-brand-600 data-[state=checked]:hover:border-brand-600 focus-visible:outline-none focus-visible:outline-1 focus-visible:outline-offset-1 focus-visible:outline-brand-500 disabled:cursor-not-allowed disabled:opacity-50',
      props.class,
    )"
  >
    <SwitchThumb
      :class="cn('pointer-events-none block h-2.5 w-2.5 rounded-full bg-ink-700 translate-x-1 rtl:-translate-x-1 transition-transform duration-150 data-[state=checked]:translate-x-[1.375rem] rtl:data-[state=checked]:-translate-x-[1.375rem] data-[state=checked]:bg-white')"
    >
      <slot name="thumb" />
    </SwitchThumb>
  </SwitchRoot>
</template>
