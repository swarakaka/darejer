<script setup lang="ts">
import { ref, computed } from 'vue'
import { Input, InputPassword } from '@/components/ui/input'
import { Plus } from 'lucide-vue-next'
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/components/ui/tooltip'
import FieldWrapper from '@/components/darejer/FieldWrapper.vue'
import CreateInDialog from '@/components/darejer/CreateInDialog.vue'
import type { DarejerComponent } from '@/types/darejer'

const props = defineProps<{
  component: DarejerComponent
  record: Record<string, unknown>
  errors: Record<string, string>
  formData?: Record<string, unknown>
}>()

const emit = defineEmits<{ (e: 'update', name: string, value: unknown): void }>()

const decimals = computed(() =>
  typeof props.component.decimals === 'number' ? (props.component.decimals as number) : 0,
)

function formatNumeric(raw: unknown): string {
  if (raw === '' || raw === null || raw === undefined) return ''
  const n = Number(raw)
  if (!Number.isFinite(n)) return String(raw)
  return n.toFixed(decimals.value)
}

const initialRaw =
  (props.formData ?? props.record)[props.component.name] ?? props.component.default ?? ''

const value = ref<string | number>(
  props.component.inputType === 'number' ? formatNumeric(initialRaw) : (initialRaw as string),
)

function onInput(e: Event) {
  const val = (e.target as HTMLInputElement).value
  value.value = val
  emit('update', props.component.name, val)
}

const numberStep = computed(() => {
  if (props.component.inputType !== 'number') return undefined
  return decimals.value <= 0 ? '1' : `0.${'0'.repeat(decimals.value - 1)}1`
})

function onBlur(e: Event) {
  if (props.component.inputType !== 'number') return
  const raw = (e.target as HTMLInputElement).value
  if (raw === '' || raw === null) return
  const formatted = formatNumeric(raw)
  if (formatted === raw) return
  value.value = formatted
  emit('update', props.component.name, formatted)
}

const isRevealable = computed(
  () => props.component.inputType === 'password' && !!props.component.revealable,
)

const hasPrefix = computed(() => !!props.component.prefix)
const hasSuffix = computed(() => !!props.component.suffix && !isRevealable.value)

const suffixActionUrl = computed(
  () => (props.component.suffixActionUrl as string | undefined) ?? null,
)
const suffixActionTooltip = computed(
  () => (props.component.suffixActionTooltip as string | undefined) ?? null,
)
const hasSuffixAction = computed(() => suffixActionUrl.value !== null && !isRevealable.value)

const suffixDialogOpen = ref(false)
function openSuffixDialog() {
  if (!suffixActionUrl.value) return
  suffixDialogOpen.value = true
}
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
            hasSuffix || hasSuffixAction ? 'pe-20' : '',
          ]"
          @input="onInput"
          @blur="onBlur"
        />

        <!-- Suffix (static text) -->
        <span
          v-if="hasSuffix && !hasSuffixAction"
          class="pointer-events-none absolute inset-y-0 inset-e-0 z-10 flex max-w-[40%] items-center truncate rounded-e-md border-s border-paper-300 bg-paper-50 px-2.5 text-sm whitespace-nowrap text-ink-500 select-none"
        >
          {{ component.suffix }}
        </span>

        <!-- Suffix action: clickable button that opens an inline dialog -->
        <TooltipProvider v-if="hasSuffixAction" :delay-duration="0">
          <Tooltip>
            <TooltipTrigger as-child>
              <button
                type="button"
                class="absolute inset-y-0 inset-e-0 z-10 flex items-center justify-center rounded-e-md border-s border-paper-300 bg-paper-50 px-2.5 text-ink-500 transition-colors hover:bg-paper-100 hover:text-ink-900 focus:outline-none focus:ring-2 focus:ring-brand-500"
                :aria-label="suffixActionTooltip ?? 'Add'"
                @click="openSuffixDialog"
              >
                <Plus class="h-4 w-4" />
              </button>
            </TooltipTrigger>
            <TooltipContent v-if="suffixActionTooltip" side="top" class="text-xs">
              {{ suffixActionTooltip }}
            </TooltipContent>
          </Tooltip>
        </TooltipProvider>

        <CreateInDialog
          v-if="hasSuffixAction"
          v-model:open="suffixDialogOpen"
          :url="suffixActionUrl as string"
          mode="page"
        />
      </div>
    </template>
  </FieldWrapper>
</template>
