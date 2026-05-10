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
  record: Record<string, unknown>
  errors: Record<string, string>
  formData?: Record<string, unknown>
}>()

const emit = defineEmits<{ (e: 'update', name: string, value: unknown): void }>()

interface TableCol {
  field: string
  label: string
  type: string
  width?: string
  disabled?: boolean
  placeholder?: string
  options?: { value: string; label: string }[]
  decimals?: number
  compute?: string
  // combobox-only:
  dataUrl?: string
  keyField?: string
  labelField?: string
  labelFields?: string[]
  searchFields?: string[]
  subLabelField?: string
  imageField?: string
  optionFields?: string[]
  fillFrom?: Record<string, string> | null
  fillFromCap?: Record<string, string> | null
  filtersFrom?: Record<string, string> | null
}

type TableRow = Record<string, unknown> & { _id: number }

let nextId = 0

const columns = computed((): TableCol[] => (props.component.tableColumns as TableCol[]) ?? [])
const isAddable = computed(() => props.component.addable !== false)
const isDeletable = computed(() => props.component.deletable !== false)
const isSortable = computed(() => !!props.component.sortable)
const isDisabled = computed(() => !!props.component.disabled)
const maxRows = computed(() => props.component.maxRows as number | undefined)
const defaultRow = computed(() => (props.component.defaultRow as Record<string, unknown>) ?? {})

const externalValue = computed(
  () => (props.formData ?? props.record)[props.component.name] ?? props.component.default ?? [],
)

function buildBlankRow(): TableRow {
  const row = {
    ...Object.fromEntries(columns.value.map((c) => [c.field, defaultRow.value[c.field] ?? ''])),
    _id: nextId++,
  } as TableRow
  applyComputed(row)
  return row
}

function buildRowsFrom(value: unknown): TableRow[] {
  const arr = Array.isArray(value) ? value : []
  const seeded = arr.map((row: Record<string, unknown>) => {
    const seededRow = { ...row, _id: nextId++ } as TableRow
    applyComputed(seededRow)
    return seededRow
  })

  // Airtable-style UX: always keep one empty row at the bottom so the
  // table is immediately editable. The trailing row is stripped from
  // `emitValue()` output so the host app never persists empty placeholders.
  if (isAddable.value && (seeded.length === 0 || !isRowBlank(seeded[seeded.length - 1]))) {
    seeded.push(buildBlankRow())
  }
  return seeded
}

interface CompiledCompute {
  field: string
  deps: string[]
  fn: (...args: number[]) => unknown
}

const computedColumns = computed((): CompiledCompute[] => {
  const fieldSet = new Set(columns.value.map((c) => c.field))
  return columns.value.flatMap((col) => {
    if (!col.compute) return []
    const tokens = col.compute.match(/\b[a-zA-Z_][a-zA-Z0-9_]*\b/g) ?? []
    const deps = Array.from(new Set(tokens.filter((t) => fieldSet.has(t))))
    try {
      const fn = new Function(...deps, `"use strict"; return (${col.compute});`) as (
        ...args: number[]
      ) => unknown
      return [{ field: col.field, deps, fn }]
    } catch (e) {
      console.error(
        `[EditableTable] failed to compile compute expression for "${col.field}": ${col.compute}`,
        e,
      )
      return []
    }
  })
})

function applyComputed(row: TableRow): void {
  for (const c of computedColumns.value) {
    let result: unknown = ''
    try {
      const args = c.deps.map((d) => {
        const n = Number(row[d])
        return Number.isFinite(n) ? n : 0
      })
      result = c.fn(...args)
    } catch {
      result = ''
    }
    row[c.field] =
      typeof result === 'number' && Number.isFinite(result) ? result : (result ?? '')
  }
}

function isRowBlank(row: TableRow): boolean {
  for (const col of columns.value) {
    // Compute columns are derived from other fields, so their value
    // should not determine whether a row is blank — otherwise the
    // trailing placeholder row gets a non-empty computed value (e.g.
    // amount = qty * rate = 0) and the table thinks it's filled.
    if (col.compute) continue
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
  const last = rows.value[rows.value.length - 1]
  const payload = last && isRowBlank(last) ? rows.value.slice(0, -1) : rows.value
  const cleaned = payload.map(({ _id, ...rest }) => rest)
  lastEmitted = cleaned
  emit('update', props.component.name, cleaned)
}

watch(externalValue, (next) => {
  if (next === lastEmitted) return
  rows.value = buildRowsFrom(next)
})

function ensureTrailingBlankRow() {
  if (!isAddable.value) return
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
  rows.value = rows.value.filter((r) => r._id !== id)
  ensureTrailingBlankRow()
  emitValue()
}

function onCellInput(row: TableRow, field: string, e: Event) {
  row[field] = (e.target as HTMLInputElement).value
  applyComputed(row)
  ensureTrailingBlankRow()
  emitValue()
}

function onCellChange(row: TableRow, field: string, e: Event) {
  row[field] = (e.target as HTMLInputElement | HTMLSelectElement).value
  applyComputed(row)
  ensureTrailingBlankRow()
  emitValue()
}

function onCheckChange(row: TableRow, field: string, e: Event) {
  row[field] = (e.target as HTMLInputElement).checked
  applyComputed(row)
  ensureTrailingBlankRow()
  emitValue()
}

function onComboboxUpdate(row: TableRow, field: string, value: unknown) {
  row[field] = value
  applyComputed(row)
  ensureTrailingBlankRow()
  emitValue()
}

function onComboboxSelect(row: TableRow, col: TableCol, record: Record<string, unknown>) {
  const map = col.fillFrom
  if (map) {
    const caps = col.fillFromCap ?? {}
    const parent = props.formData ?? props.record
    for (const [rowField, recordField] of Object.entries(map)) {
      const v = record[recordField]
      if (v === undefined || v === null) continue

      const capField = caps[rowField]
      if (capField) {
        const parentTotal = Number(parent[capField])
        if (Number.isFinite(parentTotal)) {
          const otherSum = rows.value.reduce((acc, r) => {
            if (r._id === row._id) return acc
            const n = Number(r[rowField])
            return Number.isFinite(n) ? acc + n : acc
          }, 0)
          const remaining = parentTotal - otherSum
          const incoming = Number(v)
          if (Number.isFinite(incoming)) {
            row[rowField] = Math.max(0, Math.min(incoming, remaining))
            continue
          }
        }
      }

      row[rowField] = v
    }
  }
  applyComputed(row)
  ensureTrailingBlankRow()
  emitValue()
}

const gridTemplate = computed(() =>
  [
    isSortable.value ? '2rem' : '',
    ...columns.value.map((c) => c.width ?? '1fr'),
    isDeletable.value ? '2.5rem' : '',
  ]
    .filter(Boolean)
    .join(' '),
)

function formatCellValue(value: unknown, col: TableCol): string {
  if (value === null || value === undefined || value === '') return ''
  if (col.type === 'money') {
    const n = Number(value)
    if (Number.isFinite(n)) {
      const decimals = typeof col.decimals === 'number' ? col.decimals : 2
      return n.toLocaleString(undefined, {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
      })
    }
  }
  if (col.type === 'number' && typeof col.decimals === 'number') {
    const n = Number(value)
    if (Number.isFinite(n)) return n.toFixed(col.decimals)
  }
  return String(value)
}

function onMoneyInput(row: TableRow, field: string, e: Event) {
  const raw = (e.target as HTMLInputElement).value
  const cleaned = raw.replace(/,/g, '')
  row[field] = cleaned === '' ? '' : cleaned
  applyComputed(row)
  ensureTrailingBlankRow()
  emitValue()
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
        class="overflow-hidden rounded-md border bg-card"
        :class="hasError ? 'border-danger-600' : 'border-paper-200'"
      >
        <!-- Header -->
        <div
          class="grid border-b border-paper-200 bg-paper-75"
          :style="{ gridTemplateColumns: gridTemplate }"
        >
          <div v-if="isSortable" class="h-8" />
          <div
            v-for="col in columns"
            :key="col.field"
            class="flex h-8 items-center border-e border-paper-200 px-2.5 text-[10px] font-semibold tracking-[0.08em] text-ink-400 uppercase last:border-e-0"
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
            <div
              v-if="isSortable"
              class="drag-handle flex h-8 cursor-grab items-center justify-center"
            >
              <GripVertical class="h-3.5 w-3.5 text-ink-300" />
            </div>

            <div
              v-for="col in columns"
              :key="col.field"
              class="flex h-8 items-center border-e border-paper-100 last:border-e-0"
            >
              <input
                v-if="col.type === 'text' || col.type === 'number'"
                :type="col.type === 'number' ? 'number' : 'text'"
                :value="formatCellValue(row[col.field], col)"
                :disabled="isDisabled || col.disabled"
                class="h-full w-full border-none bg-transparent px-2.5 text-sm transition-colors duration-100 outline-none focus:bg-brand-50 focus:ring-0 disabled:cursor-not-allowed disabled:opacity-50"
                @input="onCellInput(row, col.field, $event)"
              />

              <input
                v-else-if="col.type === 'money'"
                type="text"
                inputmode="decimal"
                :value="formatCellValue(row[col.field], col)"
                :disabled="isDisabled || col.disabled"
                class="h-full w-full border-none bg-transparent px-2.5 text-right text-sm transition-colors duration-100 outline-none focus:bg-brand-50 focus:ring-0 disabled:cursor-not-allowed disabled:opacity-50"
                @input="onMoneyInput(row, col.field, $event)"
              />

              <EditableTableDateCell
                v-else-if="col.type === 'date'"
                :model-value="row[col.field]"
                :disabled="isDisabled || col.disabled"
                :placeholder="col.placeholder"
                class="h-full w-full"
                @update:model-value="onComboboxUpdate(row, col.field, $event)"
              />

              <EditableTableComboboxCell
                v-else-if="col.type === 'combobox'"
                :column="col"
                :model-value="row[col.field]"
                :disabled="isDisabled || col.disabled"
                :form-data="formData ?? record"
                class="h-full w-full"
                @update:model-value="onComboboxUpdate(row, col.field, $event)"
                @select="onComboboxSelect(row, col, $event)"
              />

              <select
                v-else-if="col.type === 'select'"
                :value="String(row[col.field] ?? '')"
                :disabled="isDisabled || col.disabled"
                class="h-full w-full cursor-pointer border-none bg-transparent px-2.5 text-sm outline-none focus:bg-brand-50 disabled:cursor-not-allowed disabled:opacity-50"
                @change="onCellChange(row, col.field, $event)"
              >
                <option value="">—</option>
                <option v-for="opt in col.options" :key="opt.value" :value="opt.value">
                  {{ opt.label }}
                </option>
              </select>

              <div
                v-else-if="col.type === 'checkbox'"
                class="flex h-full w-full items-center justify-center"
              >
                <input
                  type="checkbox"
                  :checked="!!row[col.field]"
                  :disabled="isDisabled || col.disabled"
                  class="h-4 w-4 cursor-pointer accent-brand-600 disabled:cursor-not-allowed disabled:opacity-50"
                  @change="onCheckChange(row, col.field, $event)"
                />
              </div>
            </div>

            <div v-if="isDeletable" class="flex h-8 items-center justify-center">
              <button
                type="button"
                :disabled="isDisabled"
                class="flex h-7 w-7 items-center justify-center rounded-sm text-ink-300 transition-colors duration-100 hover:bg-danger-50 hover:text-danger-600 disabled:opacity-40"
                @click="deleteRow(row._id)"
              >
                <Trash2 class="h-3.5 w-3.5" />
              </button>
            </div>
          </div>
        </VueDraggable>

        <!-- Add row -->
        <div
          v-if="isAddable && !isDisabled"
          class="flex items-center border-t border-paper-100 bg-paper-75 px-2.5 py-1.5"
        >
          <button
            type="button"
            :disabled="!!(maxRows && rows.length >= maxRows)"
            class="flex items-center gap-1.5 text-xs font-medium text-brand-600 transition-colors hover:text-brand-700 disabled:cursor-not-allowed disabled:opacity-40"
            @click="addRow"
          >
            <Plus class="h-3 w-3" />
            {{ __('Add row') }}
          </button>
          <span v-if="maxRows" class="ms-auto text-xs text-ink-400 tabular-nums">
            {{ rows.length }}/{{ maxRows }}
          </span>
        </div>
      </div>
    </template>
  </FieldWrapper>
</template>
