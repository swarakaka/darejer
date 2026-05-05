<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

// See Input.vue for rationale — supports both `v-model` and
// `:value` + `@input` patterns.
defineOptions({ inheritAttrs: true })

const props = defineProps<{
  modelValue?: string | number | null
  class?: HTMLAttributes['class']
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void
}>()

function onInternalInput(e: Event) {
  emit('update:modelValue', (e.target as HTMLTextAreaElement).value)
}
</script>

<template>
  <textarea
    :value="modelValue ?? undefined"
    :class="
      cn(
        `flex min-h-[5rem] w-full resize-y rounded-[2px] border border-(--input-border) bg-input px-2.5 py-2 text-[13px] text-ink-900 transition-colors duration-100 placeholder:text-ink-400 hover:border-ink-700 focus:border-brand-500 focus:shadow-[inset_0_0_0_1px_var(--color-brand-500)] focus:ring-0 focus:outline-none disabled:cursor-not-allowed disabled:bg-muted disabled:opacity-50`,
        props.class,
      )
    "
    @input="onInternalInput"
  />
</template>
