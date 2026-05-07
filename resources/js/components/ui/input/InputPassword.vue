<script setup lang="ts">
import { ref, computed } from 'vue'
import type { HTMLAttributes } from 'vue'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Eye, EyeOff } from 'lucide-vue-next'
import useTranslation from '@/composables/useTranslation'

// Reusable password input with built-in show/hide toggle.
// Supports both v-model and the value/@input fallthrough pattern, mirroring
// the base Input component, so it drops into the auth pages (v-model) and
// the screen-engine TextInputComponent (controlled value) without changes.

defineOptions({ inheritAttrs: false })

const props = defineProps<{
  modelValue?: string | number | null
  class?: HTMLAttributes['class']
  disabled?: boolean
  readonly?: boolean
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void
}>()

const { __ } = useTranslation()

const revealed = ref(false)
const inputType = computed(() => (revealed.value ? 'text' : 'password'))

function onUpdate(value: string) {
  emit('update:modelValue', value)
}
</script>

<template>
  <div class="relative w-full">
    <Input
      v-bind="$attrs"
      :model-value="modelValue ?? undefined"
      :type="inputType"
      :disabled="disabled"
      :readonly="readonly"
      :class="['pe-10', props.class]"
      @update:model-value="onUpdate"
    />
    <Button
      type="button"
      variant="ghost"
      size="icon-sm"
      tabindex="-1"
      :aria-label="revealed ? __('Hide password') : __('Show password')"
      :aria-pressed="revealed"
      :disabled="disabled || readonly"
      class="absolute inset-y-0 inset-e-0.5 my-auto text-ink-500 hover:text-ink-700"
      @click="revealed = !revealed"
    >
      <component :is="revealed ? EyeOff : Eye" />
    </Button>
  </div>
</template>
