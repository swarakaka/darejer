<script setup lang="ts">
import type { DialogContentEmits, DialogContentProps } from 'reka-ui'
import type { HTMLAttributes } from 'vue'
import { reactiveOmit } from '@vueuse/core'
import { X } from 'lucide-vue-next'
import {
  DialogClose,
  DialogContent,
  DialogOverlay,
  DialogPortal,
  useForwardPropsEmits,
} from 'reka-ui'
import { cn } from '@/lib/utils'

const props = defineProps<
  DialogContentProps & { class?: HTMLAttributes['class']; hideClose?: boolean }
>()
const emits = defineEmits<DialogContentEmits>()

const delegatedProps = reactiveOmit(props, 'class', 'hideClose')

const forwarded = useForwardPropsEmits(delegatedProps, emits)
</script>

<template>
  <DialogPortal>
    <DialogOverlay
      class="fixed inset-0 z-50 bg-ink-900/55 data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:animate-in data-[state=open]:fade-in-0"
    />
    <DialogContent
      v-bind="forwarded"
      :class="
        cn(
          `fixed start-1/2 top-1/2 z-50 grid w-full max-w-lg -translate-x-1/2 -translate-y-1/2 gap-4 rounded-[2px] border border-(--border) bg-card p-6 text-card-foreground shadow-[0_25.6px_57.6px_rgba(0,0,0,0.22),_0_4.8px_14.4px_rgba(0,0,0,0.18)] duration-150 data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=closed]:zoom-out-95 data-[state=open]:animate-in data-[state=open]:fade-in-0 data-[state=open]:zoom-in-95 rtl:translate-x-1/2`,
          props.class,
        )
      "
    >
      <slot />

      <DialogClose
        v-if="!hideClose"
        class="absolute end-3 top-3 inline-flex h-7 w-7 items-center justify-center rounded-[2px] text-ink-700 transition-colors hover:bg-paper-150 hover:text-ink-900 focus:outline-none focus-visible:outline-1 focus-visible:outline-brand-500 disabled:pointer-events-none"
      >
        <X class="h-4 w-4" />
        <span class="sr-only">Close</span>
      </DialogClose>
    </DialogContent>
  </DialogPortal>
</template>
