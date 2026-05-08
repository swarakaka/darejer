<script setup lang="ts">
import { Delete } from 'lucide-vue-next'

/**
 * On-screen numeric keypad for touchscreens. Edits a string value (kept as
 * a string so leading zeros, partial decimals, and high-precision amounts
 * survive intermediate states without going through float).
 */
const props = defineProps<{
  modelValue: string
  /** Decimal places allowed (default 2 for currency). 0 = integer-only. */
  decimals?: number
  /** Show a "C" clear key. */
  showClear?: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const decimals = () => props.decimals ?? 2

function press(key: string) {
  let next = props.modelValue ?? ''

  if (key === 'back') {
    next = next.slice(0, -1)
  } else if (key === 'C') {
    next = ''
  } else if (key === '.') {
    if (decimals() === 0) return
    if (next.includes('.')) return
    next = next === '' ? '0.' : next + '.'
  } else {
    // Block extra decimals once we already have `decimals()` past the point.
    if (next.includes('.')) {
      const fraction = next.split('.')[1] ?? ''
      if (fraction.length >= decimals()) return
    }
    // Replace a leading "0" so users don't end up with "0123".
    if (next === '0' && key !== '.') {
      next = key
    } else {
      next = next + key
    }
  }

  emit('update:modelValue', next)
}

const keys = [
  ['7', '8', '9'],
  ['4', '5', '6'],
  ['1', '2', '3'],
]

const decimalDisabled = (props.decimals ?? 2) === 0
</script>

<template>
  <div class="grid grid-cols-3 gap-2">
    <template v-for="row in keys" :key="row[0]">
      <button
        v-for="k in row"
        :key="k"
        type="button"
        class="h-16 rounded-sm border border-ink-200 bg-white text-[22px] font-semibold tabular-nums text-ink-900 transition-[background-color,border-color,transform] hover:border-brand-400 hover:bg-brand-50 active:translate-y-px active:bg-brand-100 focus-visible:outline-1 focus-visible:outline-offset-1 focus-visible:outline-brand-500"
        @click="press(k)"
      >
        {{ k }}
      </button>
    </template>

    <button
      type="button"
      class="h-16 rounded-sm border border-ink-200 bg-white text-[22px] font-semibold tabular-nums text-ink-900 transition-[background-color,border-color,transform] hover:border-brand-400 hover:bg-brand-50 active:translate-y-px active:bg-brand-100 focus-visible:outline-1 focus-visible:outline-offset-1 focus-visible:outline-brand-500 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:border-ink-200 disabled:hover:bg-white"
      :disabled="decimalDisabled"
      @click="press('.')"
    >
      .
    </button>
    <button
      type="button"
      class="h-16 rounded-sm border border-ink-200 bg-white text-[22px] font-semibold tabular-nums text-ink-900 transition-[background-color,border-color,transform] hover:border-brand-400 hover:bg-brand-50 active:translate-y-px active:bg-brand-100 focus-visible:outline-1 focus-visible:outline-offset-1 focus-visible:outline-brand-500"
      @click="press('0')"
    >
      0
    </button>
    <button
      type="button"
      class="flex h-16 items-center justify-center rounded-sm border border-ink-200 bg-paper-100 text-ink-700 transition-[background-color,border-color,transform] hover:border-ink-400 hover:bg-paper-150 hover:text-ink-900 active:translate-y-px active:bg-paper-200 focus-visible:outline-1 focus-visible:outline-offset-1 focus-visible:outline-brand-500"
      :aria-label="'Backspace'"
      @click="press('back')"
    >
      <Delete class="size-6" />
    </button>

    <button
      v-if="showClear"
      type="button"
      class="col-span-3 h-11 rounded-sm border border-transparent bg-transparent text-[13px] font-semibold uppercase tracking-[0.06em] text-danger-600 transition-colors hover:bg-danger-50 hover:text-danger-700 active:bg-danger-100 focus-visible:outline-1 focus-visible:outline-offset-1 focus-visible:outline-brand-500"
      @click="press('C')"
    >
      C
    </button>
  </div>
</template>
