<script setup lang="ts">
import { ref, reactive }    from 'vue'
import { Textarea }         from '@/components/ui/textarea'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import FieldWrapper         from '@/components/darejer/FieldWrapper.vue'
import { useLanguages }     from '@/composables/useLanguages'
import useTranslation       from '@/composables/useTranslation'
import type { DarejerComponent } from '@/types/darejer'

const { __ } = useTranslation()

const props = defineProps<{
    component: DarejerComponent
    record:    Record<string, unknown>
    errors:    Record<string, string>
    formData?: Record<string, unknown>
}>()

const emit = defineEmits<{ (e: 'update', name: string, value: unknown): void }>()

const {
    languages,
    defaultLanguage,
    currentLocale,
    isMultilingual,
    localeLabel,
    localeName,
    parseTranslatable,
} = useLanguages()

const rawValue = (props.formData ?? props.record)[props.component.name]
    ?? props.component.default
    ?? ''

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
                :readonly="(component.readonly as boolean)"
                :disabled="(component.disabled as boolean)"
                :value="translations[currentLocale] ?? translations[defaultLanguage] ?? ''"
                class="w-full text-sm resize-y"
                :class="hasError ? 'border-danger-600' : ''"
                @input="onSingleInput"
            />

            <!-- Multi-language: tabbed -->
            <div
                v-else
                class="border border-slate-200 rounded-md overflow-hidden"
                :class="hasError ? 'border-danger-600' : ''"
            >
                <Tabs
                    :default-value="currentLocale"
                    @update:model-value="(v) => activeTab = String(v ?? '')"
                >

                    <!-- Tab list -->
                    <TabsList class="flex items-center gap-px px-1.5 py-1 bg-slate-75 border-b border-slate-200 rounded-none h-auto">
                        <TabsTrigger
                            v-for="locale in languages"
                            :key="locale"
                            :value="locale"
                            class="flex items-center gap-1.5 h-6 px-2 rounded text-xs font-medium
                                   data-[state=active]:bg-white data-[state=active]:text-brand-600
                                   data-[state=active]:border data-[state=active]:border-slate-200
                                   text-slate-500 hover:text-slate-700 transition-colors duration-100"
                        >
                            <span
                                class="inline-flex items-center justify-center w-6 h-3.5
                                       bg-slate-200 rounded text-[9px] font-bold tracking-wide
                                       data-[state=active]:bg-brand-100 data-[state=active]:text-brand-700"
                                :class="localeError(locale) ? 'bg-danger-100 text-danger-700' : ''"
                            >
                                {{ localeLabel(locale) }}
                            </span>
                            {{ localeName(locale) }}
                            <!-- Dot indicator: error (red) wins over filled (brand) -->
                            <span
                                v-if="localeError(locale)"
                                class="w-1 h-1 rounded-full bg-danger-600"
                            />
                            <span
                                v-else-if="(translations[locale] ?? '').trim().length > 0"
                                class="w-1 h-1 rounded-full bg-brand-600"
                            />
                        </TabsTrigger>
                    </TabsList>

                    <!-- Tab panels -->
                    <TabsContent
                        v-for="locale in languages"
                        :key="locale"
                        :value="locale"
                        class="p-0 mt-0"
                    >
                        <Textarea
                            :id="`${component.name}-${locale}`"
                            :placeholder="(component.placeholder as string) ?? __('Enter :name text…', { name: localeName(locale) })"
                            :rows="(component.rows as number) ?? 4"
                            :readonly="(component.readonly as boolean)"
                            :disabled="(component.disabled as boolean)"
                            :value="translations[locale] ?? ''"
                            class="w-full text-sm resize-y border-none rounded-none focus:ring-0"
                            @input="onInput(locale, $event)"
                        />
                        <p
                            v-if="localeError(locale)"
                            class="px-2 py-1 text-xs text-danger-600 leading-snug border-t border-slate-200 bg-danger-50"
                        >
                            {{ localeError(locale) }}
                        </p>
                    </TabsContent>

                </Tabs>
            </div>

        </template>
    </FieldWrapper>
</template>
