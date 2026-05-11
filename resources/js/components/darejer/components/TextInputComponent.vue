<script setup lang="ts">
import { ref, computed } from 'vue'
import { Input, InputPassword } from '@/components/ui/input'
import FieldWrapper from '@/components/darejer/FieldWrapper.vue'
import type { DarejerComponent } from '@/types/darejer'

const props = defineProps<{
  component: DarejerComponent
  record: Record<string, unknown>
  errors: Record<string, string>
  formData?: Record<string, unknown>
}>()

const emit = defineEmits<{ (e: 'update', name: string, value: unknown): void }>()

const value = ref(
  (props.formData ?? props.record)[props.component.name] ?? props.component.default ?? '',
)

function onInput(e: Event) {
  const val = (e.target as HTMLInputElement).value
  value.value = val
  emit('update', props.component.name, val)
}

const decimals = computed(() =>
  typeof props.component.decimals === 'number' ? (props.component.decimals as number) : 0,
)

const numberStep = computed(() => {
  if (props.component.inputType !== 'number') return undefined
  return decimals.value <= 0 ? '1' : `0.${'0'.repeat(decimals.value - 1)}1`
})

function onBlur(e: Event) {
  if (props.component.inputType !== 'number') return
  const raw = (e.target as HTMLInputElement).value
  if (raw === '' || raw === null) return
  const n = Number(raw)
  if (!Number.isFinite(n)) return
  const formatted = n.toFixed(decimals.value)
  if (formatted === raw) return
  value.value = formatted
  emit('update', props.component.name, formatted)
}

const isRevealable = computed(
  () => props.component.inputType === 'password' && !!props.component.revealable,
)

const hasPrefix = computed(() => !!props.component.prefix)
const hasSuffix = computed(() => !!props.component.suffix && !isRevealable.value)
</script>

<template>
  <FieldWrapper :component="component" :record="record" :errors="errors" :form-data="formData">
    <template #default="{ hasError }">
      <div class="relative flex w-full items-center">
        <!-- Prefix -->
        <span
          v-if="hasPrefix"
          class="pointer-events-none absolute inset-y-0 inset-s-0 z-10 flex max-w-[40%] items-center truncate rounded-s-md border-e border-paper-300 bg-paper-50 px-2.5 text-sm whitespace-nowrap text-ink-500 select-none"
        >
          {{ component.prefix }}
        </span>

        <component
          :is="isRevealable ? InputPassword : Input"
          :id="component.name"
          :name="component.name"
          :type="!isRevealable ? ((component.inputType as string) ?? 'text') : undefined"
          :placeholder="(component.placeholder as string) ?? ''"
          :value="value as string"
          :readonly="component.readonly as boolean"
          :disabled="component.disabled as boolean"
          :maxlength="component.maxLength as number"
          :autofocus="component.autofocus as boolean"
          :step="numberStep"
          class="w-full"
          :class="[
            hasError ? 'border-danger-600' : '',
            hasPrefix ? 'ps-20' : '',
            hasSuffix ? 'pe-20' : '',
          ]"
          @input="onInput"
          @blur="onBlur"
        />

        <!-- Suffix -->
        <span
          v-if="hasSuffix"
          class="pointer-events-none absolute inset-y-0 inset-e-0 z-10 flex max-w-[40%] items-center truncate rounded-e-md border-s border-paper-300 bg-paper-50 px-2.5 text-sm whitespace-nowrap text-ink-500 select-none"
        >
          {{ component.suffix }}
        </span>
      </div>
    </template>
  </FieldWrapper>
</template>
