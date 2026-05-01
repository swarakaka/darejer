<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Plus, Trash2, GripVertical } from 'lucide-vue-next'
import { VueDraggable } from 'vue-draggable-plus'
import FieldWrapper from '@/components/darejer/FieldWrapper.vue'
import EditableTableComboboxCell from '@/components/darejer/components/EditableTableComboboxCell.vue'
import EditableTableDateCell from '@/components/darejer/components/EditableTableDateCell.vue'
import useTranslation from '@/composables/useTranslation'
import type { DarejerComponent } from '@/types/darejer'

const { __ } = useTranslation()

const props = defineProps<{
    component: DarejerComponent
    record:    Record<string, unknown>
    errors:    Record<string, string>
    formData?: Record<string, unknown>
}>()

const emit = defineEmits<{ (e: 'update', name: string, value: unknown): void }>()

interface TableCol {
    field:    string
    label:    string
    type:     string
    width?:   string
    disabled?: boolean
    placeholder?: string
    options?: { value: string; label: string }[]
    // combobox-only:
    dataUrl?:      string
    keyField?:     string
    labelField?:   string
    labelFields?:  string[]
    searchFields?: string[]
    subLabelField?: string
    imageField?:   string
    optionFields?: string[]
    fillFrom?:     Record<string, string> | null
}

type TableRow = Record<string, unknown> & { _id: number }

let nextId = 0

const columns     = computed((): TableCol[] => (props.component.tableColumns as TableCol[]) ?? [])
const isAddable   = computed(() => props.component.addable   !== false)
const isDeletable = computed(() => props.component.deletable !== false)
const isSortable  = computed(() => !!props.component.sortable)
const isDisabled  = computed(() => !!props.component.disabled)
const maxRows     = computed(() => props.component.maxRows as number | undefined)
const defaultRow  = computed(() => (props.component.defaultRow as Record<string, unknown>) ?? {})

const externalValue = computed(() =>
    (props.formData ?? props.record)[props.component.name]
        ?? props.component.default ?? []
)

function buildBlankRow(): TableRow {
    return {
        ...Object.fromEntries(
            columns.value.map(c => [c.field, defaultRow.value[c.field] ?? '']),
        ),
        _id: nextId++,
    } as TableRow
}

function buildRowsFrom(value: unknown): TableRow[] {
    const arr = Array.isArray(value) ? value : []
    const seeded = arr.map((row: Record<string, unknown>) => ({ ...row, _id: nextId++ }))

    // Airtable-style UX: always keep one empty row at the bottom so the
    // table is immediately editable. The trailing row is stripped from
    // `emitValue()` output so the host app never persists empty placeholders.
    if (isAddable.value && (seeded.length === 0 || !isRowBlank(seeded[seeded.length - 1]))) {
        seeded.push(buildBlankRow())
    }
    return seeded
}

function isRowBlank(row: TableRow): boolean {
    for (const col of columns.value) {
        const v = row[col.field]
        const d = defaultRow.value[col.field] ?? ''
        if (v !== d && v !== '' && v !== null && v !== undefined) return false
    }
    return true
}

const rows = ref<TableRow[]>(buildRowsFrom(externalValue.value))

// Track the last array we emitted so we can ignore the parent's echo when it
// writes our own emission back into formData. Anything other than that exact
// reference is a real external change (e.g. a prefill) and replaces our rows.
let lastEmitted: unknown = null

function emitValue() {
    // Strip a single trailing blank row (the editable placeholder) before
    // emitting so validators / persistence never see it.
    const last     = rows.value[rows.value.length - 1]
    const payload  = last && isRowBlank(last) ? rows.value.slice(0, -1) : rows.value
    const cleaned  = payload.map(({ _id, ...rest }) => rest)
    lastEmitted = cleaned
    emit('update', props.component.name, cleaned)
}

watch(externalValue, (next) => {
    if (next === lastEmitted) return
    rows.value = buildRowsFrom(next)
})

function ensureTrailingBlankRow() {
    if (!isAddable.value)                            return
    if (maxRows.value && rows.value.length >= maxRows.value) return

    const last = rows.value[rows.value.length - 1]
    if (!last || !isRowBlank(last)) {
        rows.value = [...rows.value, buildBlankRow()]
    }
}

function addRow() {
    if (maxRows.value && rows.value.length >= maxRows.value) return
    rows.value = [...rows.value, buildBlankRow()]
    emitValue()
}

function deleteRow(id: number) {
    rows.value = rows.value.filter(r => r._id !== id)
    ensureTrailingBlankRow()
    emitValue()
}

function onCellInput(row: TableRow, field: string, e: Event) {
    row[field] = (e.target as HTMLInputElement).value
    ensureTrailingBlankRow()
    emitValue()
}

function onCellChange(row: TableRow, field: string, e: Event) {
    row[field] = (e.target as HTMLInputElement | HTMLSelectElement).value
    ensureTrailingBlankRow()
    emitValue()
}

function onCheckChange(row: TableRow, field: string, e: Event) {
    row[field] = (e.target as HTMLInputElement).checked
    ensureTrailingBlankRow()
    emitValue()
}

function onComboboxUpdate(row: TableRow, field: string, value: unknown) {
    row[field] = value
    ensureTrailingBlankRow()
    emitValue()
}

function onComboboxSelect(row: TableRow, col: TableCol, record: Record<string, unknown>) {
    const map = col.fillFrom
    if (map) {
        for (const [rowField, recordField] of Object.entries(map)) {
            const v = record[recordField]
            if (v !== undefined && v !== null) {
                row[rowField] = v
            }
        }
    }
    ensureTrailingBlankRow()
    emitValue()
}

const gridTemplate = computed(() => [
    isSortable.value  ? '2rem'   : '',
    ...columns.value.map(c => c.width ?? '1fr'),
    isDeletable.value ? '2.5rem' : '',
].filter(Boolean).join(' '))

function formatCellValue(value: unknown, _type: string): string {
    if (value === null || value === undefined) return ''
    return String(value)
}
</script>

<template>
    <FieldWrapper
        :component="component"
        :record="record"
        :errors="errors"
        :form-data="formData"
        class="col-span-full"
    >
        <template #default="{ hasError }">
            <div
                class="border rounded-md overflow-hidden bg-white"
                :class="hasError ? 'border-danger-600' : 'border-paper-200'"
            >
                <!-- Header -->
                <div
                    class="grid bg-paper-75 border-b border-paper-200"
                    :style="{ gridTemplateColumns: gridTemplate }"
                >
                    <div v-if="isSortable" class="h-8" />
                    <div
                        v-for="col in columns"
                        :key="col.field"
                        class="flex items-center px-2.5 h-8 text-[10px] font-semibold uppercase tracking-[0.08em] text-ink-400 border-e border-paper-200 last:border-e-0"
                    >
                        {{ col.label }}
                    </div>
                    <div v-if="isDeletable" class="h-8" />
                </div>

                <!-- Rows. vue-draggable-plus uses a DEFAULT slot + v-for
                     (not a `#item` slot). That's why earlier iterations
                     rendered nothing and the whole table looked empty. -->
                <VueDraggable
                    v-model="rows"
                    :disabled="!isSortable || isDisabled"
                    handle=".drag-handle"
                    ghost-class="opacity-40"
                    @end="emitValue"
                >
                    <div
                        v-for="row in rows"
                        :key="row._id"
                        class="grid border-b border-paper-100 last:border-b-0 hover:bg-paper-50"
                        :style="{ gridTemplateColumns: gridTemplate }"
                    >
                        <div v-if="isSortable" class="flex items-center justify-center h-8 drag-handle cursor-grab">
                            <GripVertical class="w-3.5 h-3.5 text-ink-300" />
                        </div>

                        <div
                            v-for="col in columns"
                            :key="col.field"
                            class="flex items-center border-e border-paper-100 last:border-e-0 h-8"
                        >
                            <input
                                v-if="col.type === 'text' || col.type === 'number'"
                                :type="col.type === 'number' ? 'number' : 'text'"
                                :value="formatCellValue(row[col.field], col.type)"
                                :disabled="isDisabled || col.disabled"
                                class="w-full h-full px-2.5 text-sm bg-transparent border-none
                                       outline-none focus:bg-brand-50 focus:ring-0
                                       disabled:opacity-50 disabled:cursor-not-allowed
                                       transition-colors duration-100"
                                @input="onCellInput(row, col.field, $event)"
                            />

                            <EditableTableDateCell
                                v-else-if="col.type === 'date'"
                                :model-value="row[col.field]"
                                :disabled="isDisabled || col.disabled"
                                :placeholder="col.placeholder"
                                class="w-full h-full"
                                @update:model-value="onComboboxUpdate(row, col.field, $event)"
                            />

                            <EditableTableComboboxCell
                                v-else-if="col.type === 'combobox'"
                                :column="col"
                                :model-value="row[col.field]"
                                :disabled="isDisabled || col.disabled"
                                class="w-full h-full"
                                @update:model-value="onComboboxUpdate(row, col.field, $event)"
                                @select="onComboboxSelect(row, col, $event)"
                            />

                            <select
                                v-else-if="col.type === 'select'"
                                :value="String(row[col.field] ?? '')"
                                :disabled="isDisabled || col.disabled"
                                class="w-full h-full px-2.5 text-sm bg-transparent border-none
                                       outline-none focus:bg-brand-50 cursor-pointer
                                       disabled:opacity-50 disabled:cursor-not-allowed"
                                @change="onCellChange(row, col.field, $event)"
                            >
                                <option value="">—</option>
                                <option v-for="opt in col.options" :key="opt.value" :value="opt.value">
                                    {{ opt.label }}
                                </option>
                            </select>

                            <div v-else-if="col.type === 'checkbox'" class="flex items-center justify-center w-full h-full">
                                <input
                                    type="checkbox"
                                    :checked="!!row[col.field]"
                                    :disabled="isDisabled || col.disabled"
                                    class="w-4 h-4 accent-brand-600 cursor-pointer
                                           disabled:opacity-50 disabled:cursor-not-allowed"
                                    @change="onCheckChange(row, col.field, $event)"
                                />
                            </div>
                        </div>

                        <div v-if="isDeletable" class="flex items-center justify-center h-8">
                            <button
                                type="button"
                                :disabled="isDisabled"
                                class="flex items-center justify-center w-7 h-7 rounded-sm
                                       text-ink-300 hover:text-danger-600 hover:bg-danger-50
                                       transition-colors duration-100 disabled:opacity-40"
                                @click="deleteRow(row._id)"
                            >
                                <Trash2 class="w-3.5 h-3.5" />
                            </button>
                        </div>
                    </div>
                </VueDraggable>

                <!-- Add row -->
                <div
                    v-if="isAddable && !isDisabled"
                    class="flex items-center px-2.5 py-1.5 border-t border-paper-100 bg-paper-75"
                >
                    <button
                        type="button"
                        :disabled="!!(maxRows && rows.length >= maxRows)"
                        class="flex items-center gap-1.5 text-xs text-brand-600 font-medium
                               hover:text-brand-700 transition-colors disabled:opacity-40
                               disabled:cursor-not-allowed"
                        @click="addRow"
                    >
                        <Plus class="w-3 h-3" />
                        {{ __('Add row') }}
                    </button>
                    <span
                        v-if="maxRows"
                        class="ms-auto text-xs text-ink-400 tabular-nums"
                    >
                        {{ rows.length }}/{{ maxRows }}
                    </span>
                </div>
            </div>
        </template>
    </FieldWrapper>
</template>
