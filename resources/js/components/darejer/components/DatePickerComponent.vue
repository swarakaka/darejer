<script setup lang="ts">
import { ref, computed, watch }    from 'vue'
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
}                           from '@/components/ui/popover'
import { Calendar }         from '@/components/ui/calendar'
import { CalendarIcon, X }  from 'lucide-vue-next'
import FieldWrapper         from '@/components/darejer/FieldWrapper.vue'
import useTranslation       from '@/composables/useTranslation'
import type { DarejerComponent } from '@/types/darejer'
import {
    CalendarDate,
    DateFormatter,
    getLocalTimeZone,
    parseDate,
    type DateValue,
} from '@internationalized/date'

const { __ } = useTranslation()

const props = defineProps<{
    component: DarejerComponent
    record:    Record<string, unknown>
    errors:    Record<string, string>
    formData?: Record<string, unknown>
}>()

const emit = defineEmits<{ (e: 'update', name: string, value: unknown): void }>()

const open = ref(false)

// Accept the value the form actually carries — either a plain `YYYY-MM-DD`
// (from a DatePicker default) or a Laravel/Carbon ISO string with a time
// component (from `casts: ['date' => 'date']`, which serializes to e.g.
// `2026-04-25T00:00:00.000000Z`). `parseDate()` only handles the former,
// so trim to the date portion first.
function parseInitial(v: unknown): CalendarDate | undefined {
    if (v == null) return undefined
    const s = String(v).slice(0, 10)
    if (!/^\d{4}-\d{2}-\d{2}$/.test(s)) return undefined
    try { return parseDate(s) } catch { return undefined }
}

function readRecordValue(): unknown {
    return (props.formData ?? props.record)[props.component.name]
        ?? props.component.default ?? null
}

const selected = ref<CalendarDate | undefined>(parseInitial(readRecordValue()))

// Keep the picker in sync if the record/formData value changes after mount
// (e.g. navigating between Edit screens, or the parent form initialising
// asynchronously). Without this watch the picker only ever shows whatever
// it was constructed with.
watch(
    () => readRecordValue(),
    (next) => {
        const parsed = parseInitial(next)
        if (parsed && (!selected.value || selected.value.compare(parsed) !== 0)) {
            selected.value = parsed
        } else if (next == null && selected.value) {
            selected.value = undefined
        }
    },
)

const df = new DateFormatter('en-US', { dateStyle: 'medium' })

const displayValue = computed(() =>
    selected.value
        ? df.format(selected.value.toDate(getLocalTimeZone()))
        : null
)

const placeholder = computed(() =>
    (props.component.placeholder as string) ?? __('Pick a date…')
)

const minDate = computed(() => {
    const v = props.component.minDate as string | undefined
    return v ? parseDate(v) : undefined
})

const maxDate = computed(() => {
    const v = props.component.maxDate as string | undefined
    return v ? parseDate(v) : undefined
})

const disabledDates = computed(() => {
    const arr = props.component.disabledDates as string[] | undefined
    return arr?.map(d => parseDate(d)) ?? []
})

function isDateDisabled(date: DateValue): boolean {
    return disabledDates.value.some(d => d.compare(date) === 0)
}

function onSelect(date: unknown) {
    const d = Array.isArray(date) ? date[0] : (date as CalendarDate | undefined)
    selected.value = d
    if (d) open.value = false
    emit('update', props.component.name, d ? d.toString() : null)
}

function clear() {
    selected.value = undefined
    emit('update', props.component.name, null)
}
</script>

<template>
    <FieldWrapper :component="component" :record="record" :errors="errors" :form-data="formData">
        <template #default="{ hasError }">
            <Popover :open="open" @update:open="open = $event">
                <PopoverTrigger as-child>
                    <button
                        type="button"
                        :disabled="(component.disabled as boolean)"
                        class="flex items-center justify-between w-full h-8 px-2.5
                               text-sm text-start bg-card border rounded-sm
                               text-ink-900 transition-colors duration-100
                               focus:outline-none focus:ring-0
                               disabled:opacity-50 disabled:cursor-not-allowed"
                        :class="[
                            hasError ? 'border-danger-600' : 'border-paper-300',
                            open ? 'border-brand-500' : '',
                        ]"
                    >
                        <span :class="selected ? 'text-ink-900' : 'text-ink-400'">
                            {{ displayValue ?? placeholder }}
                        </span>
                        <div class="flex items-center gap-1">
                            <button
                                v-if="selected"
                                type="button"
                                class="text-ink-300 hover:text-ink-500 transition-colors"
                                @click.stop="clear"
                            >
                                <X class="w-3 h-3" />
                            </button>
                            <CalendarIcon class="w-3.5 h-3.5 text-ink-400" />
                        </div>
                    </button>
                </PopoverTrigger>

                <PopoverContent class="p-0 w-auto" align="start">
                    <Calendar
                        :model-value="selected as any"
                        :min-value="minDate as any"
                        :max-value="maxDate as any"
                        :is-date-disabled="isDateDisabled as any"
                        initial-focus
                        class="border-none"
                        @update:model-value="onSelect"
                    />
                </PopoverContent>
            </Popover>
        </template>
    </FieldWrapper>
</template>
