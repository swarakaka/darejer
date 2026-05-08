<script setup lang="ts">
import { Button } from '@/components/ui/button'
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
</script>

<template>
  <div class="grid grid-cols-3 gap-2">
    <template v-for="row in keys" :key="row[0]">
      <Button
        v-for="k in row"
        :key="k"
        type="button"
        variant="outline"
        class="h-12 text-[18px] font-semibold tabular-nums"
        @click="press(k)"
      >
        {{ k }}
      </Button>
    </template>

    <Button
      type="button"
      variant="outline"
      class="h-12 text-[18px] font-semibold tabular-nums"
      :disabled="(props.decimals ?? 2) === 0"
      @click="press('.')"
    >
      .
    </Button>
    <Button
      type="button"
      variant="outline"
      class="h-12 text-[18px] font-semibold tabular-nums"
      @click="press('0')"
    >
      0
    </Button>
    <Button
      type="button"
      variant="outline"
      class="h-12"
      @click="press('back')"
    >
      <Delete class="size-5" />
    </Button>

    <Button
      v-if="showClear"
      type="button"
      variant="ghost"
      class="col-span-3 h-10 text-[14px]"
      @click="press('C')"
    >
      C
    </Button>
  </div>
</template>
