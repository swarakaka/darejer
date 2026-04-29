<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

// Supports BOTH binding styles with zero indirection:
//
//  * `<Input v-model="x" />` — declared modelValue prop + update:modelValue
//    emit, used by Login / ForgotPassword / etc.
//  * `<Input :value="x" @input="..." />` — the `value` attribute and the
//    `input` event listener fall through to the native <input> via
//    `inheritAttrs: true`. Darejer's form components use this pattern and
//    need a controlled input with zero extra state.
//
// The native <input>'s `:value` binding is set only when `modelValue` is
// actually passed. When it's `undefined`, `:value="undefined"` is ignored
// by Vue, so the fallthrough `value` attribute from $attrs wins.

defineOptions({ inheritAttrs: true })

const props = defineProps<{
  modelValue?: string | number | null
  class?: HTMLAttributes["class"]
}>()

const emit = defineEmits<{
  (e: "update:modelValue", value: string): void
}>()

function onInternalInput(e: Event) {
  emit("update:modelValue", (e.target as HTMLInputElement).value)
}
</script>

<template>
  <input
    :value="modelValue ?? undefined"
    :class="cn('flex h-8 w-full rounded-[2px] border border-ink-500 bg-white px-2.5 text-[13px] text-ink-900 placeholder:text-ink-400 transition-colors duration-100 hover:border-ink-700 focus:border-brand-500 focus:outline-none focus:ring-0 focus:shadow-[inset_0_0_0_1px_var(--color-brand-500)] disabled:cursor-not-allowed disabled:opacity-50 disabled:bg-paper-100', props.class)"
    @input="onInternalInput"
  >
</template>
