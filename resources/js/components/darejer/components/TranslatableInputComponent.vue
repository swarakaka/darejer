<script setup lang="ts">
import { ref, reactive }     from 'vue'
import { Input }             from '@/components/ui/input'
import { Button }            from '@/components/ui/button'
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
}                           from '@/components/ui/dialog'
import { Label }            from '@/components/ui/label'
import { Globe }            from 'lucide-vue-next'
import FieldWrapper         from '@/components/darejer/FieldWrapper.vue'
import { useLanguages }     from '@/composables/useLanguages'
import type { DarejerComponent } from '@/types/darejer'

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
    (translations[currentLocale.value]
        ?? translations[defaultLanguage.value]
        ?? '') as string,
)

// Counter badge for the globe icon. Recomputed only when the dialog saves.
const filledCount = ref(computeFilled())

const dialogOpen = ref(false)

function computeFilled(): number {
    return languages.value
        .filter(l => l !== currentLocale.value)
        .filter(l => (translations[l] ?? '').trim().length > 0)
        .length
}

function emitUpdate() {
    // Emit a plain-object snapshot so the parent form doesn't see the reactive
    // proxy (and doesn't track further reads on it).
    emit('update', props.component.name, { ...translations })
}

function onMainInput(e: Event) {
    const val = (e.target as HTMLInputElement).value
    displayValue.value                = val
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
                :readonly="(component.readonly as boolean)"
                :disabled="(component.disabled as boolean)"
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
                    :readonly="(component.readonly as boolean)"
                    :disabled="(component.disabled as boolean)"
                    class="w-full pe-9"
                    :class="hasError ? 'border-danger-600' : ''"
                    @input="onMainInput"
                />

                <button
                    type="button"
                    class="absolute end-0 flex items-center justify-center w-9 h-full
                           text-slate-400 hover:text-brand-600 transition-colors duration-100
                           border-s border-slate-200 rounded-e"
                    :title="`Translate (${filledCount}/${languages.length - 1} filled)`"
                    @click="dialogOpen = true"
                >
                    <Globe class="w-3.5 h-3.5" />
                    <span
                        v-if="filledCount > 0"
                        class="absolute top-1 end-1 w-1.5 h-1.5 rounded-full bg-brand-600"
                    />
                </button>
            </div>

            <!-- Translation Dialog -->
            <Dialog :open="dialogOpen" @update:open="dialogOpen = $event">
                <DialogContent class="max-w-lg p-0 overflow-hidden">
                    <DialogHeader class="px-4 py-3 border-b border-slate-200 bg-slate-75">
                        <DialogTitle class="text-base font-semibold">
                            {{ component.label }} — Translations
                        </DialogTitle>
                    </DialogHeader>

                    <div class="px-4 py-4 space-y-4">
                        <div
                            v-for="locale in languages"
                            :key="locale"
                            class="flex flex-col gap-1"
                        >
                            <Label :for="`${component.name}-${locale}`" class="flex items-center gap-1.5">
                                <span
                                    class="inline-flex items-center justify-center w-7 h-4
                                           bg-slate-100 border border-slate-200 rounded
                                           text-[10px] font-semibold text-slate-600 tracking-wide"
                                >
                                    {{ localeLabel(locale) }}
                                </span>
                                {{ localeName(locale) }}
                                <span v-if="locale === defaultLanguage" class="text-xs text-slate-400">(default)</span>
                            </Label>
                            <Input
                                :id="`${component.name}-${locale}`"
                                :placeholder="(component.placeholder as string) ?? ''"
                                :value="translations[locale] ?? ''"
                                class="w-full"
                                @input="onDialogInput(locale, $event)"
                            />
                        </div>
                    </div>

                    <DialogFooter class="flex justify-end gap-1.5 px-4 py-3 border-t border-slate-200 bg-slate-75">
                        <Button
                            variant="outline"
                            class="h-[2.125rem] text-sm"
                            @click="dialogOpen = false"
                        >
                            Cancel
                        </Button>
                        <Button
                            class="h-[2.125rem] text-sm bg-brand-600 hover:bg-brand-700 text-white border-none"
                            @click="saveDialog"
                        >
                            Apply
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

        </template>
    </FieldWrapper>
</template>
