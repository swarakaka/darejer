<script setup lang="ts">
import { reactiveOmit } from '@vueuse/core'
import { X } from 'lucide-vue-next'
import type { DialogContentEmits, DialogContentProps } from 'reka-ui'
import { DialogClose, DialogContent, DialogOverlay, DialogPortal, useForwardPropsEmits } from 'reka-ui'
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

const props = defineProps<DialogContentProps & { class?: HTMLAttributes['class']; hideClose?: boolean }>()
const emits = defineEmits<DialogContentEmits>()

const delegatedProps = reactiveOmit(props, 'class', 'hideClose')

const forwarded = useForwardPropsEmits(delegatedProps, emits)
</script>

<template>
  <DialogPortal>
    <DialogOverlay
      class="bg-ink-900/55 data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:animate-in data-[state=open]:fade-in-0 fixed inset-0 z-50"
    />
    <DialogContent
      v-bind="forwarded"
      :class="
        cn(
          `bg-card text-card-foreground data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=closed]:zoom-out-95 data-[state=open]:animate-in data-[state=open]:fade-in-0 data-[state=open]:zoom-in-95 fixed start-1/2 top-1/2 z-50 grid w-full max-w-lg -translate-x-1/2 -translate-y-1/2 gap-4 rounded-[2px] border border-(--border) p-6 shadow-[0_25.6px_57.6px_rgba(0,0,0,0.22),_0_4.8px_14.4px_rgba(0,0,0,0.18)] duration-150 rtl:translate-x-1/2`,
          props.class,
        )
      "
    >
      <slot />

      <DialogClose
        v-if="!hideClose"
        class="text-ink-700 hover:bg-paper-150 hover:text-ink-900 focus-visible:outline-brand-500 absolute end-3 top-3 inline-flex h-7 w-7 items-center justify-center rounded-[2px] transition-colors focus:outline-none focus-visible:outline-1 disabled:pointer-events-none"
      >
        <X class="h-4 w-4" />
        <span class="sr-only">Close</span>
      </DialogClose>
    </DialogContent>
  </DialogPortal>
</template>
