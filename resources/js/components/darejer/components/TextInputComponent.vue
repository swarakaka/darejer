<script setup lang="ts">
import { ref, computed } from 'vue'
import { Input } from '@/components/ui/input'
import FieldWrapper from '@/components/darejer/FieldWrapper.vue'
import { Eye, EyeOff } from 'lucide-vue-next'
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

const isRevealable = computed(
  () => props.component.inputType === 'password' && !!props.component.revealable,
)
const revealed = ref(false)
const effectiveType = computed(() =>
  isRevealable.value && revealed.value ? 'text' : ((props.component.inputType as string) ?? 'text'),
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

        <Input
          :id="component.name"
          :name="component.name"
          :type="effectiveType"
          :placeholder="(component.placeholder as string) ?? ''"
          :value="value as string"
          :readonly="component.readonly as boolean"
          :disabled="component.disabled as boolean"
          :maxlength="component.maxLength as number"
          :autofocus="component.autofocus as boolean"
          class="w-full"
          :class="[
            hasError ? 'border-danger-600' : '',
            hasPrefix ? 'ps-20' : '',
            hasSuffix ? 'pe-20' : '',
            isRevealable ? 'pe-9' : '',
          ]"
          @input="onInput"
        />

        <!-- Suffix -->
        <span
          v-if="hasSuffix"
          class="pointer-events-none absolute inset-y-0 inset-e-0 z-10 flex max-w-[40%] items-center truncate rounded-e-md border-s border-paper-300 bg-paper-50 px-2.5 text-sm whitespace-nowrap text-ink-500 select-none"
        >
          {{ component.suffix }}
        </span>

        <!-- Reveal toggle -->
        <button
          v-if="isRevealable"
          type="button"
          tabindex="-1"
          :aria-label="revealed ? 'Hide password' : 'Show password'"
          :aria-pressed="revealed"
          class="absolute inset-y-0 inset-e-0 z-10 flex items-center justify-center px-2 text-ink-500 transition-colors hover:text-ink-700 focus:text-ink-700 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
          :disabled="(component.disabled as boolean) || (component.readonly as boolean)"
          @click="revealed = !revealed"
        >
          <component :is="revealed ? EyeOff : Eye" class="size-4" />
        </button>
      </div>
    </template>
  </FieldWrapper>
</template>
