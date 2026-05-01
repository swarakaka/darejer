<script setup lang="ts">
import type { DialogContentEmits, DialogContentProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { reactiveOmit } from "@vueuse/core"
import { X } from "lucide-vue-next"
import {
  DialogClose,
  DialogContent,
  DialogOverlay,
  DialogPortal,
  useForwardPropsEmits,
} from "reka-ui"
import { cn } from "@/lib/utils"

const props = defineProps<DialogContentProps & { class?: HTMLAttributes["class"] }>()
const emits = defineEmits<DialogContentEmits>()

const delegatedProps = reactiveOmit(props, "class")

const forwarded = useForwardPropsEmits(delegatedProps, emits)
</script>

<template>
  <DialogPortal>
    <DialogOverlay
      class="fixed inset-0 z-50 bg-ink-900/55 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0"
    />
    <DialogContent
      v-bind="forwarded"
      :class="
        cn(
          'fixed start-1/2 top-1/2 z-50 grid w-full max-w-lg -translate-x-1/2 rtl:translate-x-1/2 -translate-y-1/2 gap-4 border border-(--border) bg-card text-card-foreground p-6 rounded-[2px] shadow-[0_25.6px_57.6px_rgba(0,0,0,0.22),_0_4.8px_14.4px_rgba(0,0,0,0.18)] duration-150 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95',
          props.class,
        )"
    >
      <slot />

      <DialogClose
        class="absolute end-3 top-3 w-7 h-7 inline-flex items-center justify-center rounded-[2px] text-ink-700 hover:bg-paper-150 hover:text-ink-900 transition-colors focus:outline-none focus-visible:outline-1 focus-visible:outline-brand-500 disabled:pointer-events-none"
      >
        <X class="w-4 h-4" />
        <span class="sr-only">Close</span>
      </DialogClose>
    </DialogContent>
  </DialogPortal>
</template>
