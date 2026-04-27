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
      'peer inline-flex h-5 w-9 shrink-0 cursor-pointer items-center rounded-full border border-paper-300 bg-paper-200 transition-colors duration-150 data-[state=checked]:bg-brand-600 data-[state=checked]:border-brand-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-50',
      props.class,
    )"
  >
    <SwitchThumb
      :class="cn('pointer-events-none block h-3.5 w-3.5 rounded-full bg-white translate-x-0.5 transition-transform duration-150 data-[state=checked]:translate-x-[1.1rem]')"
    >
      <slot name="thumb" />
    </SwitchThumb>
  </SwitchRoot>
</template>
