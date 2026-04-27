<script setup lang="ts">
import type { RadioGroupItemProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { reactiveOmit } from "@vueuse/core"
import { Circle } from "lucide-vue-next"
import {
  RadioGroupIndicator,
  RadioGroupItem,
  useForwardProps,
} from "reka-ui"
import { cn } from "@/lib/utils"

const props = defineProps<RadioGroupItemProps & { class?: HTMLAttributes["class"] }>()

const delegatedProps = reactiveOmit(props, "class")

const forwardedProps = useForwardProps(delegatedProps)
</script>

<template>
  <RadioGroupItem
    v-bind="forwardedProps"
    :class="
      cn(
        'peer aspect-square h-4 w-4 rounded-full border border-paper-300 bg-white cursor-pointer transition-colors data-[state=checked]:border-brand-600 focus:outline-none focus-visible:ring-1 focus-visible:ring-brand-500 focus-visible:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-50',
        props.class,
      )
    "
  >
    <RadioGroupIndicator
      class="flex items-center justify-center"
    >
      <Circle class="h-2 w-2 fill-brand-600 text-brand-600" />
    </RadioGroupIndicator>
  </RadioGroupItem>
</template>
