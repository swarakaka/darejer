<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import AppBreadcrumbs from '@/components/darejer/AppBreadcrumbs.vue'
import DarejerActions from '@/components/darejer/DarejerActions.vue'
import {
  ChevronUp,
  ChevronDown,
  ChevronsUpDown,
  ChevronLeft,
  ChevronRight,
  Search,
  Inbox,
  SlidersHorizontal,
  X,
  Pencil,
  Eye,
  Trash2,
  Trash,
  RotateCcw,
  MoreHorizontal,
  CheckCircle2,
  CalendarIcon,
} from 'lucide-vue-next'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import { Calendar } from '@/components/ui/calendar'
import { CalendarDate, DateFormatter, getLocalTimeZone, parseDate } from '@internationalized/date'
import type { DarejerAction, DependOnRule } from '@/types/darejer'
import useTranslation from '@/composables/useTranslation'
import { evaluateDependOn } from '@/composables/useDependOn'

defineOptions({ layout: AppLayout })

const { __ } = useTranslation()

const page = usePage<{ darejer?: { locale?: string } }>()
const localeKey = computed(() => page.props.darejer?.locale ?? 'en')

interface GridColumn {
  field: string
  label: string
  sortable?: boolean
  searchable?: boolean
  width?: string
  hidden?: boolean
  align?: 'left' | 'center' | 'right'
  badge?: string
  badgeLabels?: string
  textColorBy?: string
  textColorMap?: string
}

interface FilterDef {
  field: string
  label: string
  type: string
  options?: { value: string; label: string }[]
  placeholder?: string
}

type DateRange = { from?: string; to?: string }
type FilterValue = string | DateRange

function isDateRange(v: unknown): v is DateRange {
  return typeof v === 'object' && v !== null && !Array.isArray(v)
}

function defaultFilterValue(filter: FilterDef): FilterValue {
  return filter.type === 'daterange' ? { from: '', to: '' } : ''
}

function isFilterActive(v: FilterValue): boolean {
  if (typeof v === 'string') return v !== ''
  return Boolean(v.from) || Boolean(v.to)
}

interface GridRowAction {
  label: string
  icon?: string
  type: string
  urlPattern?: string
  method?: string
  dialog?: boolean
  confirm?: string
  variant: string
  dependOn?: DependOnRule
}

interface TableData {
  data: Record<string, unknown>[]
  total: number
  current_page: number
  last_page: number
  per_page: number
  from: number
  to: number
}

const props = defineProps<{
  title: string
  columns: GridColumn[]
  filters: FilterDef[]
  rowActions: GridRowAction[]
  headerActions: DarejerAction[]
  bulkActions?: DarejerAction[]
  selectable: boolean
  rowActionsDisplay?: 'inline' | 'dropdown'
  emptyMessage?: string
  defaultSort: string
  defaultOrder: string
  tableData: TableData
  activeFilters: Record<string, string | DateRange>
  sort: string
  order: string
}>()

const sortField = ref(props.sort || props.defaultSort)
const sortOrder = ref<'asc' | 'desc'>((props.order || props.defaultOrder) as 'asc' | 'desc')
const filterValues = ref<Record<string, FilterValue>>(
  Object.fromEntries(
    props.filters.map((f) => {
      const incoming = (props.activeFilters as Record<string, unknown>)[f.field]
      if (f.type === 'daterange') {
        const v = isDateRange(incoming) ? incoming : {}
        return [f.field, { from: String(v.from ?? ''), to: String(v.to ?? '') }]
      }
      return [f.field, typeof incoming === 'string' ? incoming : '']
    }),
  ),
)
const globalSearch = ref('')
const selected = ref<Set<unknown>>(new Set())
const showFilters = ref(props.filters.length > 0)

const confirmOpen = ref(false)
const confirmMsg = ref('')
const confirmAction = ref<GridRowAction | null>(null)
const confirmRow = ref<Record<string, unknown> | null>(null)

const visibleColumns = computed(() => props.columns.filter((c) => !c.hidden))

const allSelected = computed(
  () =>
    props.tableData.data.length > 0 &&
    props.tableData.data.every((r) => selected.value.has(r.id ?? r)),
)

const selectedIds = computed<(string | number)[]>(
  () => Array.from(selected.value) as (string | number)[],
)

const hasBulkActions = computed(() => (props.bulkActions?.length ?? 0) > 0)
const hasSelection = computed(() => selected.value.size > 0)

function clearSelection() {
  selected.value = new Set()
}

const someSelected = computed(
  () => !allSelected.value && props.tableData.data.some((r) => selected.value.has(r.id ?? r)),
)

const activeFilterCount = computed(
  () => Object.values(filterValues.value).filter(isFilterActive).length,
)

function navigate(extra: Record<string, unknown> = {}) {
  const filterParams: Record<string, unknown> = {}
  for (const [field, v] of Object.entries(filterValues.value)) {
    if (typeof v === 'string') {
      if (v !== '') filterParams[field] = v
    } else {
      const obj: Record<string, string> = {}
      if (v.from) obj.from = v.from
      if (v.to) obj.to = v.to
      if (Object.keys(obj).length) filterParams[field] = obj
    }
  }

  const params: Record<string, unknown> = {
    sort: sortField.value,
    order: sortOrder.value,
    search: globalSearch.value || undefined,
    ...filterParams,
    ...extra,
  }
  Object.keys(params).forEach((k) => {
    if (params[k] === '' || params[k] === undefined) delete params[k]
  })

  router.get(window.location.pathname, params as Record<string, string | number>, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}

function toggleSort(field: string, sortable?: boolean) {
  if (!sortable) return
  if (sortField.value === field) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortField.value = field
    sortOrder.value = 'asc'
  }
  navigate({ page: 1 })
}

let searchTimer: ReturnType<typeof setTimeout>
watch(globalSearch, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => navigate({ page: 1 }), 300)
})

let filterTimer: ReturnType<typeof setTimeout>
function onFilterChange() {
  clearTimeout(filterTimer)
  filterTimer = setTimeout(() => navigate({ page: 1 }), 400)
}

function resetFilters() {
  filterValues.value = Object.fromEntries(
    props.filters.map((f) => [f.field, defaultFilterValue(f)]),
  )
  navigate({ page: 1 })
}

// Reka UI's <SelectItem> rejects an empty-string value, so the legacy
// `<option value="">All</option>` pattern can't be ported verbatim. We
// model "no filter applied" as this sentinel internally and translate
// it back to '' before pushing values to the URL.
const ALL_SENTINEL = '__all__'

function selectModelValue(field: string): string {
  const v = filterValues.value[field]
  if (typeof v !== 'string' || v === '' || v == null) return ALL_SENTINEL
  return v
}

function stringValue(field: string): string {
  const v = filterValues.value[field]
  return typeof v === 'string' ? v : ''
}

function onSelectChange(field: string, val: unknown) {
  filterValues.value[field] = val === ALL_SENTINEL || val == null ? '' : String(val)
  onFilterChange()
}

function onTextInput(field: string, e: Event) {
  filterValues.value[field] = (e.target as HTMLInputElement).value
  onFilterChange()
}

function getRangeFrom(field: string): string {
  const v = filterValues.value[field]
  return isDateRange(v) ? (v.from ?? '') : ''
}

function getRangeTo(field: string): string {
  const v = filterValues.value[field]
  return isDateRange(v) ? (v.to ?? '') : ''
}

function setRangeFrom(field: string, value: string) {
  const v = filterValues.value[field]
  filterValues.value[field] = {
    from: value,
    to: isDateRange(v) ? (v.to ?? '') : '',
  }
  onFilterChange()
}

function setRangeTo(field: string, value: string) {
  const v = filterValues.value[field]
  filterValues.value[field] = {
    from: isDateRange(v) ? (v.from ?? '') : '',
    to: value,
  }
  onFilterChange()
}

function onRangeFromSelect(field: string, date: unknown) {
  const d = Array.isArray(date) ? date[0] : (date as CalendarDate | undefined)
  setRangeFrom(field, d ? d.toString() : '')
  if (d) datePopoverOpen.value[`${field}:from`] = false
}

function onRangeToSelect(field: string, date: unknown) {
  const d = Array.isArray(date) ? date[0] : (date as CalendarDate | undefined)
  setRangeTo(field, d ? d.toString() : '')
  if (d) datePopoverOpen.value[`${field}:to`] = false
}

const dateFormatter = new DateFormatter('en-US', { dateStyle: 'medium' })

function parseToCalendarDate(s: string): CalendarDate | undefined {
  if (!s) return undefined
  const trimmed = s.slice(0, 10)
  if (!/^\d{4}-\d{2}-\d{2}$/.test(trimmed)) return undefined
  try {
    return parseDate(trimmed)
  } catch {
    return undefined
  }
}

function formatDate(s: string): string | null {
  const d = parseToCalendarDate(s)
  return d ? dateFormatter.format(d.toDate(getLocalTimeZone())) : null
}

const datePopoverOpen = ref<Record<string, boolean>>({})

function onDateSelect(field: string, date: unknown) {
  const d = Array.isArray(date) ? date[0] : (date as CalendarDate | undefined)
  filterValues.value[field] = d ? d.toString() : ''
  if (d) datePopoverOpen.value[field] = false
  onFilterChange()
}

const pages = computed(() => {
  const t = props.tableData.last_page
  const c = props.tableData.current_page
  const delta = 2
  const range: (number | '...')[] = []

  for (let i = Math.max(1, c - delta); i <= Math.min(t, c + delta); i++) {
    range.push(i)
  }
  if ((range[0] as number) > 1) {
    if ((range[0] as number) > 2) range.unshift('...')
    range.unshift(1)
  }
  if ((range[range.length - 1] as number) < t) {
    if ((range[range.length - 1] as number) < t - 1) range.push('...')
    range.push(t)
  }
  return range
})

function goToPage(p: number) {
  navigate({ page: p })
}

function toggleRow(id: unknown) {
  if (selected.value.has(id)) selected.value.delete(id)
  else selected.value.add(id)
  selected.value = new Set(selected.value)
}

function toggleAll() {
  if (allSelected.value) {
    selected.value = new Set()
  } else {
    selected.value = new Set(props.tableData.data.map((r) => r.id ?? r))
  }
}

function resolveUrl(pattern: string, row: Record<string, unknown>): string {
  return pattern.replace(/\{(\w+)\}/g, (_, key) => String(row[key] ?? ''))
}

function handleRowAction(action: GridRowAction, row: Record<string, unknown>) {
  if (action.confirm) {
    confirmMsg.value = action.confirm
    confirmAction.value = action
    confirmRow.value = row
    confirmOpen.value = true
    return
  }
  executeAction(action, row)
}

function executeAction(action: GridRowAction, row: Record<string, unknown>) {
  if (!action.urlPattern) return
  const url = resolveUrl(action.urlPattern, row)
  const method = (action.method ?? (action.type === 'delete' ? 'DELETE' : 'GET')).toUpperCase()
  if (method === 'DELETE') {
    router.delete(url)
  } else if (method === 'PATCH') {
    router.patch(url, {})
  } else if (method === 'PUT') {
    router.put(url, {})
  } else if (method === 'POST') {
    router.post(url, {})
  } else if (action.dialog) {
    router.visit(url, { data: { _dialog: '1' } })
  } else {
    router.visit(url)
  }
}

function rowActionsFor(row: Record<string, unknown>): GridRowAction[] {
  return props.rowActions.filter((a) => evaluateDependOn(a.dependOn, row))
}

function executeConfirmed() {
  if (confirmAction.value && confirmRow.value) {
    executeAction(confirmAction.value, confirmRow.value)
  }
  confirmOpen.value = false
}

function formatCell(value: unknown, _col: object | unknown): unknown {
  return value
}

function badgeKey(value: unknown): string {
  if (value === true) return '1'
  if (value === false) return '0'
  return value == null ? '' : String(value)
}

function badgeClass(col: GridColumn, value: unknown): string {
  if (!col.badge) return ''
  let map: Record<string, string> = {}
  try {
    map = JSON.parse(col.badge)
  } catch {
    map = {}
  }
  const variant = map[badgeKey(value)] ?? 'neutral'
  const classes: Record<string, string> = {
    success: 'bg-success-50 text-success-700 ring-success-100',
    warning: 'bg-warning-50 text-warning-700 ring-warning-100',
    danger: 'bg-danger-50 text-danger-700 ring-danger-100',
    info: 'bg-brand-50 text-brand-700 ring-brand-100',
    neutral: 'bg-paper-100 text-ink-600 ring-paper-200',
  }
  return classes[variant] ?? classes.neutral
}

function badgeLabel(col: GridColumn, value: unknown): string {
  const key = badgeKey(value)
  if (!col.badgeLabels) return key
  let labels: Record<string, string> = {}
  try {
    labels = JSON.parse(col.badgeLabels)
  } catch {
    labels = {}
  }
  return labels[key] ?? key
}

function readRowPath(row: Record<string, unknown>, path: string): unknown {
  return path.split('.').reduce<unknown>(
    (acc, segment) =>
      acc && typeof acc === 'object' ? (acc as Record<string, unknown>)[segment] : undefined,
    row,
  )
}

function textColorClass(col: GridColumn, row: Record<string, unknown>): string {
  if (!col.textColorBy || !col.textColorMap) return ''
  let map: Record<string, string> = {}
  try {
    map = JSON.parse(col.textColorMap)
  } catch {
    map = {}
  }
  const variant = map[badgeKey(readRowPath(row, col.textColorBy))]
  if (!variant) return ''
  const classes: Record<string, string> = {
    success: 'text-success-700',
    warning: 'text-warning-700',
    danger: 'text-danger-700',
    info: 'text-brand-700',
    neutral: 'text-ink-600',
  }
  return classes[variant] ?? ''
}

const iconMap: Record<string, unknown> = {
  Pencil,
  Eye,
  Trash,
  Trash2,
  RotateCcw,
  MoreHorizontal,
}
const resolveIcon = (name?: string) => (name ? (iconMap[name] ?? null) : null)

const activeFilterEntries = computed(() =>
  Object.entries(filterValues.value)
    .filter(([, v]) => isFilterActive(v))
    .map(([field, value]) => {
      const def = props.filters.find((f) => f.field === field)
      const label = def?.label ?? field
      let display: string
      if (def?.type === 'daterange' && isDateRange(value)) {
        const from = formatDate(value.from ?? '') ?? '…'
        const to = formatDate(value.to ?? '') ?? '…'
        display = `${from} → ${to}`
      } else if (def?.type === 'select' && typeof value === 'string') {
        display = def.options?.find((o) => o.value === value)?.label ?? value
      } else if (def?.type === 'date' && typeof value === 'string') {
        display = formatDate(value) ?? value
      } else {
        display = typeof value === 'string' ? value : ''
      }
      return { field, label, display }
    }),
)

function clearFilter(field: string) {
  const def = props.filters.find((f) => f.field === field)
  filterValues.value[field] = def ? defaultFilterValue(def) : ''
  navigate({ page: 1 })
}
</script>

<template>
  <div class="flex h-full flex-col overflow-hidden bg-paper-100">
    <!-- Page header — refined hero with subtle gradient -->
    <header class="relative shrink-0 overflow-hidden border-b border-paper-200 bg-card">
      <div
        class="pointer-events-none absolute inset-0 opacity-[0.35]"
        style="
          background-image: radial-gradient(
            circle at 1px 1px,
            var(--color-paper-200) 1px,
            transparent 0
          );
          background-size: 20px 20px;
        "
      />
      <div
        class="pointer-events-none absolute inset-y-0 inset-e-0 w-2/3 bg-linear-to-s from-brand-50/60 via-white/0 to-transparent"
      />

      <div class="relative flex items-start justify-between gap-6 px-6 pt-5 pb-5">
        <div class="flex min-w-0 items-start gap-3">
          <div class="flex min-w-0 flex-col">
            <AppBreadcrumbs class="mb-2" />
            <h1 class="text-[28px] leading-[1.05] font-semibold tracking-[-0.02em] text-ink-900">
              {{ title }}
            </h1>
          </div>
        </div>

        <div class="flex shrink-0 flex-col items-end gap-2">
          <button
            v-if="filters.length"
            type="button"
            class="flex h-9 items-center gap-1.5 rounded-md border px-3 text-[12.5px] font-semibold shadow-[0_1px_0_rgba(0,0,0,0.02)] transition-all"
            :class="
              showFilters
                ? `border-brand-200 bg-brand-50 text-brand-800 hover:bg-brand-100`
                : `border-paper-300 bg-card text-ink-700 hover:border-paper-400 hover:bg-paper-75`
            "
            @click="showFilters = !showFilters"
          >
            <SlidersHorizontal class="h-3.5 w-3.5" />
            {{ __('Filters') }}
            <span
              v-if="activeFilterCount > 0"
              class="inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-brand-600 px-1 text-[9px] font-bold text-white tabular-nums"
            >
              {{ activeFilterCount }}
            </span>
          </button>

          <div class="flex items-center gap-2">
            <span
              class="inline-flex items-center gap-1.5 rounded-sm bg-paper-100 px-2 py-0.5 text-[10.5px] font-bold tracking-[0.12em] text-ink-600 uppercase tabular-nums ring-1 ring-paper-200 ring-inset"
            >
              {{ tableData.total.toLocaleString() }}
              <span class="font-semibold text-ink-400">{{ __('records') }}</span>
            </span>
            <span
              v-if="hasSelection"
              class="inline-flex items-center gap-1 rounded-sm bg-brand-50 px-2 py-0.5 text-[10.5px] font-bold tracking-[0.12em] text-brand-700 uppercase tabular-nums ring-1 ring-brand-100 ring-inset"
            >
              <CheckCircle2 class="h-3 w-3" />
              {{ selected.size }} {{ __('selected') }}
            </span>
          </div>
        </div>
      </div>
    </header>

    <!-- Action Pane — under breadcrumbs and title -->
    <div class="flex flex-wrap items-center justify-end gap-1.5 px-6 pt-6 print:hidden">
      <DarejerActions :actions="headerActions" placement="header" />
    </div>

    <!-- Content -->
    <div class="flex-1 overflow-y-auto px-6 py-5">
      <!-- Active filter chips -->
      <div v-if="activeFilterEntries.length" class="mb-3 flex flex-wrap items-center gap-2">
        <span class="text-[10.5px] font-bold tracking-[0.12em] text-ink-500 uppercase">
          {{ __('Active filters') }}
        </span>
        <span
          v-for="entry in activeFilterEntries"
          :key="entry.field"
          class="inline-flex h-6 items-center gap-1.5 rounded-full bg-card ps-2 pe-1 text-[11px] font-semibold text-brand-800 shadow-[0_1px_0_rgba(0,0,0,0.02)] ring-1 ring-brand-200 ring-inset"
        >
          <span class="text-[10px] font-bold tracking-[0.1em] text-brand-500 uppercase"
            >{{ entry.label }}:</span
          >
          <span class="font-medium text-ink-700 tabular-nums">{{ entry.display }}</span>
          <button
            type="button"
            class="inline-flex h-4 w-4 items-center justify-center rounded-full text-brand-600 transition-colors hover:bg-brand-100"
            :title="__('Clear')"
            @click="clearFilter(entry.field)"
          >
            <X class="h-2.5 w-2.5" />
          </button>
        </span>
        <button
          type="button"
          class="text-[11px] font-semibold text-ink-500 underline-offset-2 transition-colors hover:text-ink-800 hover:underline"
          @click="resetFilters"
        >
          {{ __('Clear all') }}
        </button>
      </div>

      <!-- Filter bar -->
      <div
        v-if="showFilters && filters.length"
        class="relative mb-4 flex flex-wrap items-end gap-3 rounded-md border border-paper-200 bg-card p-4 shadow-[0_1px_0_rgba(0,0,0,0.02)]"
      >
        <span
          class="absolute inset-x-0 top-0 h-0.5 rounded-t-md bg-linear-to-e from-brand-500 via-brand-300 to-transparent"
        />
        <div
          v-for="filter in filters"
          :key="filter.field"
          class="flex min-w-[10rem] flex-col gap-1.5"
        >
          <Label
            :for="`filter-${filter.field}`"
            class="text-[10.5px] font-bold tracking-[0.12em] text-ink-500 uppercase"
          >
            {{ filter.label }}
          </Label>

          <Input
            v-if="filter.type === 'text'"
            :id="`filter-${filter.field}`"
            type="text"
            :placeholder="filter.placeholder ?? ''"
            :value="stringValue(filter.field)"
            @input="(e: Event) => onTextInput(filter.field, e)"
          />

          <Select
            v-else-if="filter.type === 'select'"
            :key="`${filter.field}:${localeKey}`"
            :model-value="selectModelValue(filter.field)"
            @update:model-value="(v: unknown) => onSelectChange(filter.field, v)"
          >
            <SelectTrigger :id="`filter-${filter.field}`">
              <SelectValue :placeholder="__('All')" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem :value="ALL_SENTINEL">{{ __('All') }}</SelectItem>
              <SelectItem v-for="opt in filter.options" :key="opt.value" :value="opt.value">
                {{ opt.label }}
              </SelectItem>
            </SelectContent>
          </Select>

          <Select
            v-else-if="filter.type === 'boolean'"
            :key="`${filter.field}:${localeKey}`"
            :model-value="selectModelValue(filter.field)"
            @update:model-value="(v: unknown) => onSelectChange(filter.field, v)"
          >
            <SelectTrigger :id="`filter-${filter.field}`">
              <SelectValue :placeholder="__('All')" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem :value="ALL_SENTINEL">{{ __('All') }}</SelectItem>
              <SelectItem value="1">{{ __('Yes') }}</SelectItem>
              <SelectItem value="0">{{ __('No') }}</SelectItem>
            </SelectContent>
          </Select>

          <Select
            v-else-if="filter.type === 'trashed'"
            :key="`${filter.field}:${localeKey}`"
            :model-value="selectModelValue(filter.field)"
            @update:model-value="(v: unknown) => onSelectChange(filter.field, v)"
          >
            <SelectTrigger :id="`filter-${filter.field}`">
              <SelectValue :placeholder="__('Without deleted')" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem :value="ALL_SENTINEL">{{ __('Without deleted') }}</SelectItem>
              <SelectItem value="with">{{ __('All') }}</SelectItem>
              <SelectItem value="only">{{ __('Only deleted') }}</SelectItem>
            </SelectContent>
          </Select>

          <Popover
            v-else-if="filter.type === 'date'"
            :open="datePopoverOpen[filter.field] ?? false"
            @update:open="datePopoverOpen[filter.field] = $event"
          >
            <PopoverTrigger as-child>
              <button
                :id="`filter-${filter.field}`"
                type="button"
                class="flex h-9 w-full items-center justify-between rounded-md border bg-card px-3 text-start text-[13px] text-ink-900 transition-colors duration-100 hover:border-ink-700 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/15 focus:outline-none"
                :class="[datePopoverOpen[filter.field] ? 'border-brand-500' : `border-paper-300`]"
              >
                <span :class="stringValue(filter.field) ? 'text-ink-900' : `text-ink-400`">
                  {{
                    formatDate(stringValue(filter.field)) ??
                    filter.placeholder ??
                    __('Pick a date…')
                  }}
                </span>
                <div class="flex items-center gap-1">
                  <button
                    v-if="stringValue(filter.field)"
                    type="button"
                    class="text-ink-300 transition-colors hover:text-ink-500"
                    @click.stop="clearFilter(filter.field)"
                  >
                    <X class="h-3 w-3" />
                  </button>
                  <CalendarIcon class="h-3.5 w-3.5 text-ink-400" />
                </div>
              </button>
            </PopoverTrigger>
            <PopoverContent class="w-auto p-0" align="start">
              <Calendar
                :model-value="parseToCalendarDate(stringValue(filter.field)) as any"
                initial-focus
                class="border-none"
                @update:model-value="(d: unknown) => onDateSelect(filter.field, d)"
              />
            </PopoverContent>
          </Popover>

          <div v-else-if="filter.type === 'daterange'" class="flex items-center gap-1.5">
            <Popover
              :open="datePopoverOpen[`${filter.field}:from`] ?? false"
              @update:open="datePopoverOpen[`${filter.field}:from`] = $event"
            >
              <PopoverTrigger as-child>
                <button
                  :id="`filter-${filter.field}-from`"
                  type="button"
                  class="flex h-9 min-w-[8.5rem] items-center justify-between rounded-md border bg-card px-3 text-start text-[13px] text-ink-900 transition-colors duration-100 hover:border-ink-700 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/15 focus:outline-none"
                  :class="[
                    datePopoverOpen[`${filter.field}:from`]
                      ? 'border-brand-500'
                      : 'border-paper-300',
                  ]"
                >
                  <span :class="getRangeFrom(filter.field) ? 'text-ink-900' : 'text-ink-400'">
                    {{ formatDate(getRangeFrom(filter.field)) ?? __('From…') }}
                  </span>
                  <div class="flex items-center gap-1">
                    <button
                      v-if="getRangeFrom(filter.field)"
                      type="button"
                      class="text-ink-300 transition-colors hover:text-ink-500"
                      @click.stop="setRangeFrom(filter.field, '')"
                    >
                      <X class="h-3 w-3" />
                    </button>
                    <CalendarIcon class="h-3.5 w-3.5 text-ink-400" />
                  </div>
                </button>
              </PopoverTrigger>
              <PopoverContent class="w-auto p-0" align="start">
                <Calendar
                  :model-value="parseToCalendarDate(getRangeFrom(filter.field)) as any"
                  initial-focus
                  class="border-none"
                  @update:model-value="(d: unknown) => onRangeFromSelect(filter.field, d)"
                />
              </PopoverContent>
            </Popover>

            <span class="text-[12px] text-ink-400">→</span>

            <Popover
              :open="datePopoverOpen[`${filter.field}:to`] ?? false"
              @update:open="datePopoverOpen[`${filter.field}:to`] = $event"
            >
              <PopoverTrigger as-child>
                <button
                  :id="`filter-${filter.field}-to`"
                  type="button"
                  class="flex h-9 min-w-[8.5rem] items-center justify-between rounded-md border bg-card px-3 text-start text-[13px] text-ink-900 transition-colors duration-100 hover:border-ink-700 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/15 focus:outline-none"
                  :class="[
                    datePopoverOpen[`${filter.field}:to`] ? 'border-brand-500' : 'border-paper-300',
                  ]"
                >
                  <span :class="getRangeTo(filter.field) ? 'text-ink-900' : 'text-ink-400'">
                    {{ formatDate(getRangeTo(filter.field)) ?? __('To…') }}
                  </span>
                  <div class="flex items-center gap-1">
                    <button
                      v-if="getRangeTo(filter.field)"
                      type="button"
                      class="text-ink-300 transition-colors hover:text-ink-500"
                      @click.stop="setRangeTo(filter.field, '')"
                    >
                      <X class="h-3 w-3" />
                    </button>
                    <CalendarIcon class="h-3.5 w-3.5 text-ink-400" />
                  </div>
                </button>
              </PopoverTrigger>
              <PopoverContent class="w-auto p-0" align="start">
                <Calendar
                  :model-value="parseToCalendarDate(getRangeTo(filter.field)) as any"
                  initial-focus
                  class="border-none"
                  @update:model-value="(d: unknown) => onRangeToSelect(filter.field, d)"
                />
              </PopoverContent>
            </Popover>
          </div>
        </div>

        <button
          v-if="activeFilterCount > 0"
          type="button"
          class="ms-auto flex h-9 items-center gap-1.5 rounded-md px-3 text-[12.5px] font-semibold text-ink-500 transition-colors hover:bg-paper-100 hover:text-ink-800"
          @click="resetFilters"
        >
          <X class="h-3.5 w-3.5" />
          {{ __('Clear') }} ({{ activeFilterCount }})
        </button>
      </div>

      <!-- Table card -->
      <div
        class="relative overflow-hidden rounded-md border border-paper-200 bg-card shadow-[0_1px_0_rgba(0,0,0,0.02)]"
      >
        <!-- Bulk-action strip -->
        <div
          v-if="hasBulkActions && hasSelection"
          class="relative flex items-center gap-3 border-b border-brand-100 bg-linear-to-e from-brand-50 to-brand-50/60 px-4 py-2.5"
        >
          <span class="absolute inset-y-0 inset-s-0 w-0.5 bg-brand-500" />
          <CheckCircle2 class="h-4 w-4 text-brand-600" />
          <span class="text-[12px] font-bold text-brand-800 tabular-nums">
            {{ __(':count selected', { count: selected.size }) }}
          </span>
          <button
            type="button"
            class="text-[11.5px] font-semibold text-brand-700 underline-offset-2 transition-colors hover:text-brand-900 hover:underline"
            @click="clearSelection"
          >
            {{ __('Clear') }}
          </button>
          <div class="ms-auto">
            <DarejerActions
              :actions="bulkActions ?? []"
              :selected="selectedIds"
              :form-data="activeFilters as Record<string, unknown>"
              :on-bulk-success="clearSelection"
              placement="header"
            />
          </div>
        </div>

        <!-- Table toolbar -->
        <div
          class="flex items-center gap-3 border-b border-paper-200 bg-gradient-to-b from-paper-75 to-card px-3 py-2.5"
        >
          <div class="relative max-w-xs flex-1">
            <Search
              class="pointer-events-none absolute start-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-ink-400"
            />
            <input
              v-model="globalSearch"
              type="search"
              :placeholder="__('Search…')"
              class="h-8 w-full rounded-md border border-paper-300 bg-card ps-9 pe-3 text-[13px] transition-colors placeholder:text-ink-400 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/15 focus:outline-none"
            />
          </div>
          <span
            class="ms-auto inline-flex items-center gap-1.5 text-[11px] font-semibold text-ink-500 tabular-nums"
          >
            <span class="text-ink-700">{{ tableData.from }}–{{ tableData.to }}</span>
            <span class="text-ink-300">/</span>
            <span>{{ tableData.total.toLocaleString() }}</span>
          </span>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
          <table class="w-full border-collapse">
            <thead>
              <tr class="sticky top-0 z-10 border-b border-paper-200 bg-paper-75">
                <th v-if="selectable" class="h-10 w-10 px-3">
                  <input
                    type="checkbox"
                    :checked="allSelected"
                    :indeterminate.prop="someSelected"
                    class="h-3.5 w-3.5 cursor-pointer rounded-sm align-middle accent-brand-600"
                    @change="toggleAll"
                  />
                </th>
                <th
                  v-for="col in visibleColumns"
                  :key="col.field"
                  class="h-10 px-3 text-start whitespace-nowrap"
                  :class="[
                    col.sortable
                      ? `cursor-pointer transition-colors select-none hover:bg-paper-100`
                      : '',
                    col.align === 'right' ? 'text-end' : '',
                    col.align === 'center' ? 'text-center' : '',
                  ]"
                  :style="col.width ? { width: col.width } : {}"
                  @click="toggleSort(col.field, col.sortable)"
                >
                  <div
                    class="flex items-center gap-1.5"
                    :class="
                      col.align === 'right'
                        ? 'justify-end'
                        : col.align === 'center'
                          ? `justify-center`
                          : ''
                    "
                  >
                    <span
                      class="text-[10.5px] font-bold tracking-[0.12em] uppercase"
                      :class="sortField === col.field ? 'text-brand-700' : `text-ink-500`"
                    >
                      {{ col.label }}
                    </span>
                    <template v-if="col.sortable">
                      <ChevronUp
                        v-if="sortField === col.field && sortOrder === 'asc'"
                        class="h-3 w-3 text-brand-600"
                      />
                      <ChevronDown
                        v-else-if="sortField === col.field && sortOrder === 'desc'"
                        class="h-3 w-3 text-brand-600"
                      />
                      <ChevronsUpDown v-else class="h-3 w-3 text-ink-300" />
                    </template>
                  </div>
                </th>
                <th v-if="rowActions.length" class="h-10 w-16 px-3" />
              </tr>
            </thead>

            <tbody>
              <tr v-if="tableData.data.length === 0">
                <td
                  :colspan="
                    visibleColumns.length + (selectable ? 1 : 0) + (rowActions.length ? 1 : 0)
                  "
                  class="px-3 py-16"
                >
                  <div class="relative flex flex-col items-center gap-3 text-center">
                    <div
                      class="pointer-events-none absolute inset-0 -m-6 rounded-md opacity-[0.5]"
                      style="
                        background-image: radial-gradient(
                          circle at 1px 1px,
                          var(--color-paper-200) 1px,
                          transparent 0
                        );
                        background-size: 16px 16px;
                        mask-image: radial-gradient(ellipse at center, black 0%, transparent 70%);
                      "
                    />
                    <div
                      class="relative inline-flex h-14 w-14 items-center justify-center rounded-md bg-gradient-to-br from-paper-75 to-paper-100 shadow-[0_2px_6px_-2px_rgba(0,0,0,0.05)] ring-1 ring-paper-200"
                    >
                      <Inbox class="h-6 w-6 text-ink-400" />
                    </div>
                    <div class="relative flex flex-col gap-1">
                      <span class="text-[14px] font-semibold tracking-tight text-ink-800">
                        {{ emptyMessage ?? __('No records found.') }}
                      </span>
                    </div>
                  </div>
                </td>
              </tr>

              <tr
                v-for="row in tableData.data"
                :key="String(row.id ?? row)"
                class="group/row relative border-b border-paper-100 transition-colors duration-75 last:border-b-0"
                :class="
                  selected.has(row.id ?? row)
                    ? `bg-brand-50/70 hover:bg-brand-50`
                    : 'hover:bg-paper-75'
                "
              >
                <!-- Leading rail on hover/selection -->
                <td v-if="selectable" class="relative h-10 px-3">
                  <span
                    class="absolute inset-y-0 start-0 w-0.5 transition-opacity"
                    :class="
                      selected.has(row.id ?? row)
                        ? 'bg-brand-500 opacity-100'
                        : `bg-brand-500 opacity-0 group-hover/row:opacity-60`
                    "
                  />
                  <input
                    type="checkbox"
                    :checked="selected.has(row.id ?? row)"
                    class="h-3.5 w-3.5 cursor-pointer rounded-sm align-middle accent-brand-600"
                    @change="toggleRow(row.id ?? row)"
                  />
                </td>

                <td
                  v-for="(col, ci) in visibleColumns"
                  :key="col.field"
                  class="relative h-10 px-3 text-[13px] text-ink-800"
                  :class="[
                    col.align === 'right' ? 'text-end tabular-nums' : '',
                    col.align === 'center' ? 'text-center' : '',
                    ci === 0 && !selectable ? '' : '',
                  ]"
                >
                  <!-- If no selectable, the first column gets the rail -->
                  <span
                    v-if="!selectable && ci === 0"
                    class="absolute inset-y-0 start-0 w-0.5 bg-brand-500 opacity-0 transition-opacity group-hover/row:opacity-60"
                  />
                  <span
                    v-if="col.badge"
                    class="inline-flex items-center rounded-sm px-1.5 py-0.5 text-[10px] font-bold tracking-[0.04em] uppercase ring-1 ring-inset"
                    :class="badgeClass(col, row[col.field])"
                  >
                    {{ badgeLabel(col, row[col.field]) }}
                  </span>
                  <span
                    v-else
                    class="block max-w-xs truncate"
                    :class="[
                      ci === 0 ? 'font-medium text-ink-900' : '',
                      textColorClass(col, row),
                    ]"
                  >
                    {{ formatCell(row[col.field], col) ?? '—' }}
                  </span>
                </td>

                <td v-if="rowActions.length" class="h-10 px-2">
                  <div class="flex items-center justify-end gap-0.5">
                    <template v-if="(rowActionsDisplay ?? 'inline') === 'dropdown'">
                      <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                          <button
                            type="button"
                            class="flex h-8 w-8 items-center justify-center rounded-md text-ink-400 opacity-60 transition-colors group-hover/row:opacity-100 hover:bg-brand-50 hover:text-brand-700"
                          >
                            <MoreHorizontal class="h-4 w-4" />
                          </button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-40">
                          <DropdownMenuItem
                            v-for="action in rowActionsFor(row)"
                            :key="action.label"
                            class="flex cursor-pointer items-center gap-2 text-sm"
                            :class="
                              action.variant === 'destructive'
                                ? `text-danger-700 focus:text-danger-700`
                                : ''
                            "
                            @click="handleRowAction(action, row)"
                          >
                            <component
                              :is="resolveIcon(action.icon)"
                              v-if="action.icon"
                              class="h-3.5 w-3.5 shrink-0"
                            />
                            {{ __(action.label) }}
                          </DropdownMenuItem>
                        </DropdownMenuContent>
                      </DropdownMenu>
                    </template>
                    <template v-else>
                      <TooltipProvider :delay-duration="0">
                        <Tooltip v-for="action in rowActionsFor(row)" :key="action.label">
                          <TooltipTrigger as-child>
                            <button
                              type="button"
                              class="flex h-8 w-8 items-center justify-center rounded-md transition-colors"
                              :class="
                                action.variant === 'destructive'
                                  ? `text-danger-600 hover:bg-danger-50 hover:text-danger-700`
                                  : `text-ink-400 hover:bg-brand-50 hover:text-brand-700`
                              "
                              :aria-label="__(action.label)"
                              @click="handleRowAction(action, row)"
                            >
                              <component
                                :is="resolveIcon(action.icon)"
                                v-if="action.icon"
                                class="h-3.5 w-3.5"
                              />
                              <span v-else class="text-[11px] font-semibold">{{
                                __(action.label)
                              }}</span>
                            </button>
                          </TooltipTrigger>
                          <TooltipContent>{{ __(action.label) }}</TooltipContent>
                        </Tooltip>
                      </TooltipProvider>
                    </template>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div
          v-if="tableData.total > tableData.per_page"
          class="flex items-center justify-between border-t border-paper-200 bg-gradient-to-b from-card to-paper-75 px-4 py-3"
        >
          <span class="text-[11.5px] text-ink-500 tabular-nums">
            {{ __('Showing') }}
            <span class="font-bold text-ink-800">{{ tableData.from }}–{{ tableData.to }}</span>
            {{ __('of') }}
            <span class="font-bold text-ink-800">{{ tableData.total.toLocaleString() }}</span>
            {{ __('records') }}
          </span>
          <div class="flex items-center gap-1">
            <button
              type="button"
              :disabled="tableData.current_page <= 1"
              class="flex h-8 w-8 items-center justify-center rounded-md border border-paper-300 bg-card text-ink-500 transition-colors hover:border-brand-200 hover:bg-brand-50 hover:text-brand-700 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:border-paper-300 disabled:hover:bg-card disabled:hover:text-ink-500 rtl:rotate-180"
              @click="goToPage(tableData.current_page - 1)"
            >
              <ChevronLeft class="h-3.5 w-3.5" />
            </button>
            <template v-for="(p, idx) in pages" :key="`${idx}-${p}`">
              <span
                v-if="p === '...'"
                class="flex h-8 w-8 items-center justify-center text-xs text-ink-300"
                >…</span
              >
              <button
                v-else
                type="button"
                class="flex h-8 w-8 items-center justify-center rounded-md border text-[12px] font-semibold tabular-nums transition-all"
                :class="
                  tableData.current_page === p
                    ? `border-brand-600 bg-gradient-to-b from-brand-500 to-brand-600 text-white shadow-[0_1px_2px_rgba(0,120,212,0.4)]`
                    : `border-paper-300 bg-card text-ink-600 hover:border-brand-200 hover:bg-brand-50 hover:text-brand-700`
                "
                @click="goToPage(p as number)"
              >
                {{ p }}
              </button>
            </template>
            <button
              type="button"
              :disabled="tableData.current_page >= tableData.last_page"
              class="flex h-8 w-8 items-center justify-center rounded-md border border-paper-300 bg-card text-ink-500 transition-colors hover:border-brand-200 hover:bg-brand-50 hover:text-brand-700 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:border-paper-300 disabled:hover:bg-card disabled:hover:text-ink-500 rtl:rotate-180"
              @click="goToPage(tableData.current_page + 1)"
            >
              <ChevronRight class="h-3.5 w-3.5" />
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Confirm dialog -->
  <Dialog :open="confirmOpen" @update:open="confirmOpen = $event">
    <DialogContent class="max-w-sm overflow-hidden p-0">
      <DialogHeader
        class="border-b border-paper-200 bg-gradient-to-b from-paper-75 to-card px-5 py-4"
      >
        <DialogTitle class="text-base font-semibold tracking-tight text-ink-900">{{
          __('Confirm')
        }}</DialogTitle>
      </DialogHeader>
      <div class="px-5 py-5">
        <DialogDescription class="text-sm leading-relaxed text-ink-700">{{
          confirmMsg
        }}</DialogDescription>
      </div>
      <DialogFooter class="flex justify-end gap-2 border-t border-paper-200 bg-paper-75 px-5 py-3">
        <Button variant="outline" class="h-9 text-sm" @click="confirmOpen = false">{{
          __('Cancel')
        }}</Button>
        <Button
          class="h-9 border-transparent bg-danger-600 text-sm text-white hover:bg-danger-700"
          @click="executeConfirmed"
        >
          {{ confirmAction?.label }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
