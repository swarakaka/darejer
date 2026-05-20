<script setup lang="ts">
import { ref, reactive } from 'vue'
import FieldWrapper from '@/components/darejer/FieldWrapper.vue'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Textarea } from '@/components/ui/textarea'
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

// Mutated in place per keystroke — avoids per-character object spreads that
// replace the ref and invalidate every upstream computed reading formData.
const translations = reactive<Record<string, string>>(parseTranslatable(rawValue))

// Active tab defaults to the user's current locale (multi-language mode).
// The single-language path edits the active locale too — see onSingleInput.
const activeTab = ref(currentLocale.value)

function emitUpdate() {
  // Snapshot so the parent form stores a plain object, not the proxy.
  emit('update', props.component.name, { ...translations })
}

function onInput(locale: string, e: Event) {
  translations[locale] = (e.target as HTMLTextAreaElement).value
  emitUpdate()
}

function onSingleInput(e: Event) {
  translations[currentLocale.value] = (e.target as HTMLTextAreaElement).value
  emitUpdate()
}

function localeError(locale: string): string | null {
  return props.errors[`${props.component.name}.${locale}`] ?? null
}
</script>

<template>
  <FieldWrapper :component="component" :record="record" :errors="errors" :form-data="formData">
    <template #default="{ hasError }">
      <!-- Single language: plain textarea -->
      <Textarea
        v-if="!isMultilingual"
        :id="component.name"
        :name="component.name"
        :placeholder="(component.placeholder as string) ?? ''"
        :rows="(component.rows as number) ?? 4"
        :readonly="component.readonly as boolean"
        :disabled="component.disabled as boolean"
        :value="translations[currentLocale] ?? translations[defaultLanguage] ?? ''"
        :dir="localeDirection(currentLocale)"
        class="w-full resize-y text-sm"
        :class="hasError ? 'border-danger-600' : ''"
        @input="onSingleInput"
      />

      <!-- Multi-language: tabbed -->
      <div
        v-else
        class="overflow-hidden rounded-md border border-slate-200"
        :class="hasError ? 'border-danger-600' : ''"
      >
        <Tabs :default-value="currentLocale" @update:model-value="(v) => (activeTab = String(v ?? ''))">
          <!-- Tab list -->
          <TabsList
            class="bg-slate-75 flex h-auto items-center gap-px rounded-none border-b border-slate-200 px-1.5 py-1"
          >
            <TabsTrigger
              v-for="locale in languages"
              :key="locale"
              :value="locale"
              class="data-[state=active]:bg-card data-[state=active]:text-brand-600 flex h-6 items-center gap-1.5 rounded px-2 text-xs font-medium text-slate-500 transition-colors duration-100 hover:text-slate-700 data-[state=active]:border data-[state=active]:border-slate-200"
            >
              <span
                class="data-[state=active]:bg-brand-100 data-[state=active]:text-brand-700 inline-flex h-3.5 w-6 items-center justify-center rounded bg-slate-200 text-[9px] font-bold tracking-wide"
                :class="localeError(locale) ? `bg-danger-100 text-danger-700` : ''"
              >
                {{ localeLabel(locale) }}
              </span>
              {{ localeName(locale) }}
              <!-- Dot indicator: error (red) wins over filled (brand) -->
              <span v-if="localeError(locale)" class="bg-danger-600 h-1 w-1 rounded-full" />
              <span
                v-else-if="(translations[locale] ?? '').trim().length > 0"
                class="bg-brand-600 h-1 w-1 rounded-full"
              />
            </TabsTrigger>
          </TabsList>

          <!-- Tab panels -->
          <TabsContent v-for="locale in languages" :key="locale" :value="locale" class="mt-0 p-0">
            <Textarea
              :id="`${component.name}-${locale}`"
              :placeholder="(component.placeholder as string) ?? __('Enter :name text…', { name: localeName(locale) })"
              :rows="(component.rows as number) ?? 4"
              :readonly="component.readonly as boolean"
              :disabled="component.disabled as boolean"
              :value="translations[locale] ?? ''"
              :dir="localeDirection(locale)"
              class="w-full resize-y rounded-none border-none text-sm focus:ring-0"
              @input="onInput(locale, $event)"
            />
            <p
              v-if="localeError(locale)"
              class="bg-danger-50 text-danger-600 border-t border-slate-200 px-2 py-1 text-xs leading-snug"
            >
              {{ localeError(locale) }}
            </p>
          </TabsContent>
        </Tabs>
      </div>
    </template>
  </FieldWrapper>
</template>
