<script setup lang="ts">
import { ref, computed }       from 'vue'
import { router }              from '@inertiajs/vue3'
import { X, CalendarIcon }     from 'lucide-vue-next'
import { Button }              from '@/components/ui/button'
import { Input }               from '@/components/ui/input'
import { Label }               from '@/components/ui/label'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
}                              from '@/components/ui/select'
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
}                              from '@/components/ui/popover'
import { Calendar }            from '@/components/ui/calendar'
import {
    CalendarDate,
    DateFormatter,
    getLocalTimeZone,
    parseDate,
}                              from '@internationalized/date'
import useTranslation          from '@/composables/useTranslation'
import type { DarejerComponent } from '@/types/darejer'

const { __ } = useTranslation()

// Reka UI's <SelectItem> rejects an empty-string value, so the legacy
// `<option value="">All</option>` pattern can't be ported verbatim. We
// model "no filter applied" as this sentinel internally and translate
// it back to '' before pushing values to the URL.
const ALL_SENTINEL = '__all__'

const props = defineProps<{
    component: DarejerComponent
    record:    Record<string, unknown>
    errors:    Record<string, string>
    formData?: Record<string, unknown>
}>()

interface FilterDef {
    field:        string
    label:        string
    type:         string
    options?:     { value: string; label: string }[]
    placeholder?: string
    default?:     unknown
}

const filters = computed((): FilterDef[] => (props.component.filters as FilterDef[]) ?? [])
const layout  = computed(() => (props.component.layout as string) ?? 'bar')
const isBar   = computed(() => layout.value === 'bar')
const isOpen  = ref(!props.component.collapsed)

const values = ref<Record<string, string>>(
    Object.fromEntries(
        filters.value.map(f => [f.field, String(f.default ?? '')])
    )
)

const activeCount = computed(() =>
    Object.values(values.value).filter(v => v !== '' && v !== null).length
)

function apply() {
    router.get(
        window.location.pathname,
        { ...values.value },
        { preserveState: true, preserveScroll: true, replace: true }
    )
}

function reset() {
    values.value = Object.fromEntries(filters.value.map(f => [f.field, '']))
    apply()
}

let applyTimer: ReturnType<typeof setTimeout>
function onFilterChange() {
    clearTimeout(applyTimer)
    applyTimer = setTimeout(() => apply(), 400)
}

function onTextInput(field: string, e: Event) {
    values.value[field] = (e.target as HTMLInputElement).value
    onFilterChange()
}

function selectModelValue(field: string): string {
    const v = values.value[field]
    return v === '' ? ALL_SENTINEL : v
}

function onSelectChange(field: string, val: unknown) {
    values.value[field] = val === ALL_SENTINEL || val == null ? '' : String(val)
    onFilterChange()
}

const dateFormatter = new DateFormatter('en-US', { dateStyle: 'medium' })

function parseToCalendarDate(s: string): CalendarDate | undefined {
    if (!s) return undefined
    const trimmed = s.slice(0, 10)
    if (!/^\d{4}-\d{2}-\d{2}$/.test(trimmed)) return undefined
    try { return parseDate(trimmed) } catch { return undefined }
}

function formatDate(s: string): string | null {
    const d = parseToCalendarDate(s)
    return d ? dateFormatter.format(d.toDate(getLocalTimeZone())) : null
}

const datePopoverOpen = ref<Record<string, boolean>>({})

function onDateSelect(field: string, date: unknown) {
    const d = Array.isArray(date) ? date[0] : (date as CalendarDate | undefined)
    values.value[field] = d ? d.toString() : ''
    if (d) datePopoverOpen.value[field] = false
    onFilterChange()
}

function clearField(field: string) {
    values.value[field] = ''
    onFilterChange()
}
</script>

<template>
    <div class="col-span-full">

        <!-- Bar layout -->
        <div
            v-if="isBar"
            class="flex flex-wrap items-end gap-3 p-3 bg-paper-75 border border-paper-200 rounded-md mb-3"
        >
            <div
                v-for="filter in filters"
                :key="filter.field"
                class="flex flex-col gap-1 min-w-[10rem]"
            >
                <Label :for="filter.field">{{ filter.label }}</Label>

                <Input
                    v-if="filter.type === 'text'"
                    :id="filter.field"
                    type="text"
                    :placeholder="filter.placeholder ?? __('Filter :label…', { label: filter.label })"
                    :value="values[filter.field]"
                    @input="(e: Event) => onTextInput(filter.field, e)"
                />

                <Select
                    v-else-if="filter.type === 'select'"
                    :model-value="selectModelValue(filter.field)"
                    @update:model-value="(v: unknown) => onSelectChange(filter.field, v)"
                >
                    <SelectTrigger :id="filter.field">
                        <SelectValue :placeholder="__('All')" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem :value="ALL_SENTINEL">{{ __('All') }}</SelectItem>
                        <SelectItem
                            v-for="opt in filter.options"
                            :key="opt.value"
                            :value="opt.value"
                        >
                            {{ opt.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <Popover
                    v-else-if="filter.type === 'date'"
                    :open="datePopoverOpen[filter.field] ?? false"
                    @update:open="datePopoverOpen[filter.field] = $event"
                >
                    <PopoverTrigger as-child>
                        <button
                            :id="filter.field"
                            type="button"
                            class="flex items-center justify-between w-full h-8 px-2.5
                                   text-[13px] text-start bg-card border rounded-[2px]
                                   text-ink-900 transition-colors duration-100
                                   hover:border-ink-700
                                   focus:outline-none focus:ring-0 focus:border-brand-500"
                            :class="[
                                datePopoverOpen[filter.field] ? 'border-brand-500' : 'border-paper-300',
                            ]"
                        >
                            <span :class="values[filter.field] ? 'text-ink-900' : 'text-ink-400'">
                                {{ formatDate(values[filter.field]) ?? (filter.placeholder ?? __('Pick a date…')) }}
                            </span>
                            <div class="flex items-center gap-1">
                                <button
                                    v-if="values[filter.field]"
                                    type="button"
                                    class="text-ink-300 hover:text-ink-500 transition-colors"
                                    @click.stop="clearField(filter.field)"
                                >
                                    <X class="w-3 h-3" />
                                </button>
                                <CalendarIcon class="w-3.5 h-3.5 text-ink-400" />
                            </div>
                        </button>
                    </PopoverTrigger>
                    <PopoverContent class="p-0 w-auto" align="start">
                        <Calendar
                            :model-value="parseToCalendarDate(values[filter.field]) as any"
                            initial-focus
                            class="border-none"
                            @update:model-value="(d: unknown) => onDateSelect(filter.field, d)"
                        />
                    </PopoverContent>
                </Popover>

                <Select
                    v-else-if="filter.type === 'boolean'"
                    :model-value="selectModelValue(filter.field)"
                    @update:model-value="(v: unknown) => onSelectChange(filter.field, v)"
                >
                    <SelectTrigger :id="filter.field">
                        <SelectValue :placeholder="__('All')" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem :value="ALL_SENTINEL">{{ __('All') }}</SelectItem>
                        <SelectItem value="1">{{ __('Yes') }}</SelectItem>
                        <SelectItem value="0">{{ __('No') }}</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <Button
                v-if="activeCount > 0"
                variant="ghost"
                class="h-8 text-sm text-ink-500 hover:text-ink-800 gap-1.5"
                @click="reset"
            >
                <X class="w-3.5 h-3.5" />
                {{ __('Clear (:count)', { count: activeCount }) }}
            </Button>
        </div>

        <!-- Sidebar layout -->
        <div v-else class="flex gap-4">
            <div
                v-if="isOpen"
                class="w-52 shrink-0 flex flex-col gap-3 p-3 border border-paper-200 rounded-md bg-paper-75 self-start"
            >
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-ink-600 uppercase tracking-wide">{{ __('Filters') }}</span>
                    <Button
                        v-if="activeCount > 0"
                        variant="link"
                        class="h-auto p-0 text-xs text-brand-600 hover:text-brand-700"
                        @click="reset"
                    >
                        {{ __('Clear all') }}
                    </Button>
                </div>

                <div
                    v-for="filter in filters"
                    :key="filter.field"
                    class="flex flex-col gap-1"
                >
                    <Label :for="`sidebar-${filter.field}`">{{ filter.label }}</Label>

                    <Input
                        v-if="filter.type === 'text'"
                        :id="`sidebar-${filter.field}`"
                        type="text"
                        :placeholder="filter.placeholder ?? ''"
                        :value="values[filter.field]"
                        @input="(e: Event) => onTextInput(filter.field, e)"
                    />

                    <Select
                        v-else-if="filter.type === 'select'"
                        :model-value="selectModelValue(filter.field)"
                        @update:model-value="(v: unknown) => onSelectChange(filter.field, v)"
                    >
                        <SelectTrigger :id="`sidebar-${filter.field}`">
                            <SelectValue :placeholder="__('All')" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="ALL_SENTINEL">{{ __('All') }}</SelectItem>
                            <SelectItem
                                v-for="opt in filter.options"
                                :key="opt.value"
                                :value="opt.value"
                            >
                                {{ opt.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <div class="flex-1 min-w-0">
                <slot />
            </div>
        </div>

    </div>
</template>
