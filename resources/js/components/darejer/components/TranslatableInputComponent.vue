<script setup lang="ts">
import { Globe } from 'lucide-vue-next'
import { ref, reactive, computed } from 'vue'
import FieldWrapper from '@/components/darejer/FieldWrapper.vue'
import { Button } from '@/components/ui/button'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { useLanguages } from '@/composables/useLanguages'
import useTranslation from '@/composables/useTranslation'
import type { DarejerComponent } from '@/types/darejer'

const { __ } = useTranslation()

const props = defineProps<{
  component: DarejerComponent
  record: Record<string, unknown>
  errors: Record<string, string>
  formData?: Record<string, unknown>
}>()

const emit = defineEmits<{ (e: 'update', name: string, value: unknown): void }>()

const {
  languages,
  defaultLanguage,
  currentLocale,
  isMultilingual,
  localeDirection,
  localeLabel,
  localeName,
  parseTranslatable,
} = useLanguages()

const rawValue = (props.formData ?? props.record)[props.component.name] ?? props.component.default ?? ''

// Reactive bag of translations, mutated in place on each keystroke. A
// writable-computed + object-spread (previous pattern) allocated a new
// object every character and replaced the ref, invalidating every upstream
// computed reading `formData[name]` on every single keystroke.
const translations = reactive<Record<string, string>>(parseTranslatable(rawValue))

// Plain ref for the main input's value — avoids the getter/setter round
// trip of a writable computed on the input's hot path.
//
// The main input edits the *user's currently-active locale*, so a user
// browsing in `en` sees and edits the English translation. Falls back to
// the configured default language if the active locale has no value yet.
const displayValue = ref<string>(
  (translations[currentLocale.value] ?? translations[defaultLanguage.value] ?? '') as string,
)

// Counter badge for the globe icon. Recomputed only when the dialog saves.
const filledCount = ref(computeFilled())

const dialogOpen = ref(false)

function localeError(locale: string): string | null {
  return props.errors[`${props.component.name}.${locale}`] ?? null
}

const hasAnyLocaleError = computed(() => languages.value.some((l) => !!localeError(l)))

function computeFilled(): number {
  return languages.value
    .filter((l) => l !== currentLocale.value)
    .filter((l) => (translations[l] ?? '').trim().length > 0).length
}

function emitUpdate() {
  // Emit a plain-object snapshot so the parent form doesn't see the reactive
  // proxy (and doesn't track further reads on it).
  emit('update', props.component.name, { ...translations })
}

function onMainInput(e: Event) {
  const val = (e.target as HTMLInputElement).value
  displayValue.value = val
  translations[currentLocale.value] = val
  emitUpdate()
}

function onDialogInput(locale: string, e: Event) {
  translations[locale] = (e.target as HTMLInputElement).value
}

function saveDialog() {
  filledCount.value = computeFilled()
  emitUpdate()
  dialogOpen.value = false
}
</script>

<template>
  <FieldWrapper :component="component" :record="record" :errors="errors" :form-data="formData">
    <template #default="{ hasError }">
      <!-- Single language: plain input -->
      <Input
        v-if="!isMultilingual"
        :id="component.name"
        :name="component.name"
        :placeholder="(component.placeholder as string) ?? ''"
        :value="displayValue"
        :readonly="component.readonly as boolean"
        :disabled="component.disabled as boolean"
        :dir="localeDirection(currentLocale)"
        class="w-full"
        :class="hasError ? 'border-danger-600' : ''"
        @input="onMainInput"
      />

      <!-- Multi-language: input + globe icon button -->
      <div v-else class="relative flex items-center">
        <Input
          :id="component.name"
          :name="component.name"
          :placeholder="(component.placeholder as string) ?? ''"
          :value="displayValue"
          :readonly="component.readonly as boolean"
          :disabled="component.disabled as boolean"
          :dir="localeDirection(currentLocale)"
          class="w-full pe-9"
          :class="hasError ? 'border-danger-600' : ''"
          @input="onMainInput"
        />

        <button
          type="button"
          class="absolute end-0 flex h-full w-9 items-center justify-center rounded-e border-s border-slate-200 transition-colors duration-100"
          :class="hasAnyLocaleError ? `text-danger-600 hover:text-danger-700` : `hover:text-brand-600 text-slate-400`"
          :title="
            __('Translate (:filled/:total filled)', {
              filled: filledCount,
              total: languages.length - 1,
            })
          "
          @click="dialogOpen = true"
        >
          <Globe class="h-3.5 w-3.5" />
          <span v-if="hasAnyLocaleError" class="bg-danger-600 absolute end-1 top-1 h-1.5 w-1.5 rounded-full" />
          <span v-else-if="filledCount > 0" class="bg-brand-600 absolute end-1 top-1 h-1.5 w-1.5 rounded-full" />
        </button>
      </div>

      <!-- Translation Dialog -->
      <Dialog :open="dialogOpen" @update:open="dialogOpen = $event">
        <DialogContent class="max-w-lg overflow-hidden p-0">
          <DialogHeader class="bg-slate-75 border-b border-slate-200 px-4 py-3">
            <DialogTitle class="text-base font-semibold">
              {{ __(':label — Translations', { label: component.label ?? '' }) }}
            </DialogTitle>
          </DialogHeader>

          <div class="space-y-4 px-4 py-4">
            <div v-for="locale in languages" :key="locale" class="flex flex-col gap-1">
              <Label :for="`${component.name}-${locale}`" class="flex items-center gap-1.5">
                <span
                  class="inline-flex h-4 w-7 items-center justify-center rounded border border-slate-200 bg-slate-100 text-[10px] font-semibold tracking-wide text-slate-600"
                >
                  {{ localeLabel(locale) }}
                </span>
                {{ localeName(locale) }}
                <span v-if="locale === defaultLanguage" class="text-xs text-slate-400">{{ __('(default)') }}</span>
              </Label>
              <Input
                :id="`${component.name}-${locale}`"
                :placeholder="(component.placeholder as string) ?? ''"
                :value="translations[locale] ?? ''"
                :dir="localeDirection(locale)"
                class="w-full"
                :class="localeError(locale) ? `border-danger-600` : ''"
                @input="onDialogInput(locale, $event)"
              />
              <p v-if="localeError(locale)" class="text-danger-600 text-xs leading-snug">
                {{ localeError(locale) }}
              </p>
            </div>
          </div>

          <DialogFooter class="bg-slate-75 flex justify-end gap-1.5 border-t border-slate-200 px-4 py-3">
            <Button variant="outline" class="h-[2.125rem] text-sm" @click="dialogOpen = false">
              {{ __('Cancel') }}
            </Button>
            <Button
              class="bg-brand-600 hover:bg-brand-700 h-[2.125rem] border-none text-sm text-white"
              @click="saveDialog"
            >
              {{ __('Apply') }}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </template>
  </FieldWrapper>
</template>
