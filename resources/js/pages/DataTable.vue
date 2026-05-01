<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { router }                from '@inertiajs/vue3'
import AppLayout                 from '@/layouts/AppLayout.vue'
import AppBreadcrumbs            from '@/components/darejer/AppBreadcrumbs.vue'
import DarejerActions            from '@/components/darejer/DarejerActions.vue'
import {
  ChevronUp, ChevronDown, ChevronsUpDown,
  ChevronLeft, ChevronRight,
  Search, Inbox,
  SlidersHorizontal, X,
  Pencil, Eye, Trash2, MoreHorizontal,
  CheckCircle2,
} from 'lucide-vue-next'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/components/ui/tooltip'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import type { DarejerAction } from '@/types/darejer'
import useTranslation from '@/composables/useTranslation'

defineOptions({ layout: AppLayout })

const { __ } = useTranslation()

interface GridColumn {
  field:       string
  label:       string
  sortable?:   boolean
  searchable?: boolean
  width?:      string
  hidden?:     boolean
  align?:      'left' | 'center' | 'right'
  badge?:      string
  badgeLabels?: string
}

interface FilterDef {
  field:        string
  label:        string
  type:         string
  options?:     { value: string; label: string }[]
  placeholder?: string
}

interface GridRowAction {
  label:       string
  icon?:       string
  type:        string
  urlPattern?: string
  dialog?:     boolean
  confirm?:    string
  variant:     string
}

interface TableData {
  data:         Record<string, unknown>[]
  total:        number
  current_page: number
  last_page:    number
  per_page:     number
  from:         number
  to:           number
}

const props = defineProps<{
  title:         string
  columns:       GridColumn[]
  filters:       FilterDef[]
  rowActions:    GridRowAction[]
  headerActions: DarejerAction[]
  bulkActions?:  DarejerAction[]
  selectable:    boolean
  rowActionsDisplay?: 'inline' | 'dropdown'
  emptyMessage?: string
  defaultSort:   string
  defaultOrder:  string
  tableData:     TableData
  activeFilters: Record<string, string>
  sort:          string
  order:         string
}>()

const sortField    = ref(props.sort  || props.defaultSort)
const sortOrder    = ref<'asc'|'desc'>((props.order || props.defaultOrder) as 'asc'|'desc')
const filterValues = ref<Record<string, string>>({ ...props.activeFilters })
const globalSearch = ref('')
const selected     = ref<Set<unknown>>(new Set())
const showFilters  = ref(props.filters.length > 0)

const confirmOpen   = ref(false)
const confirmMsg    = ref('')
const confirmAction = ref<GridRowAction | null>(null)
const confirmRow    = ref<Record<string, unknown> | null>(null)

const visibleColumns = computed(() => props.columns.filter(c => !c.hidden))

const allSelected = computed(() =>
    props.tableData.data.length > 0 &&
    props.tableData.data.every(r => selected.value.has(r.id ?? r))
)

const selectedIds = computed<(string | number)[]>(() =>
    Array.from(selected.value) as (string | number)[]
)

const hasBulkActions = computed(() => (props.bulkActions?.length ?? 0) > 0)
const hasSelection   = computed(() => selected.value.size > 0)

function clearSelection() {
  selected.value = new Set()
}

const someSelected = computed(() =>
    !allSelected.value &&
    props.tableData.data.some(r => selected.value.has(r.id ?? r))
)

const activeFilterCount = computed(() =>
    Object.values(filterValues.value).filter(v => v !== '').length
)

function navigate(extra: Record<string, unknown> = {}) {
  const params: Record<string, unknown> = {
    sort:   sortField.value,
    order:  sortOrder.value,
    search: globalSearch.value || undefined,
    ...filterValues.value,
    ...extra,
  }
  Object.keys(params).forEach(k => {
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
  filterValues.value = Object.fromEntries(props.filters.map(f => [f.field, '']))
  navigate({ page: 1 })
}

const pages = computed(() => {
  const t     = props.tableData.last_page
  const c     = props.tableData.current_page
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
  else                        selected.value.add(id)
  selected.value = new Set(selected.value)
}

function toggleAll() {
  if (allSelected.value) {
    selected.value = new Set()
  } else {
    selected.value = new Set(props.tableData.data.map(r => r.id ?? r))
  }
}

function resolveUrl(pattern: string, row: Record<string, unknown>): string {
  return pattern.replace(/\{(\w+)\}/g, (_, key) => String(row[key] ?? ''))
}

function handleRowAction(action: GridRowAction, row: Record<string, unknown>) {
  if (action.confirm) {
    confirmMsg.value    = action.confirm
    confirmAction.value = action
    confirmRow.value    = row
    confirmOpen.value   = true
    return
  }
  executeAction(action, row)
}

function executeAction(action: GridRowAction, row: Record<string, unknown>) {
  if (!action.urlPattern) return
  const url = resolveUrl(action.urlPattern, row)
  if (action.type === 'delete') {
    router.delete(url)
  } else if (action.dialog) {
    router.visit(url, { data: { _dialog: '1' } })
  } else {
    router.visit(url)
  }
}

function executeConfirmed() {
  if (confirmAction.value && confirmRow.value) {
    executeAction(confirmAction.value, confirmRow.value)
  }
  confirmOpen.value = false
}

function formatCell(value: unknown, col: Object|unknown): unknown {
  return value
}

function badgeKey(value: unknown): string {
  if (value === true)  return '1'
  if (value === false) return '0'
  return value == null ? '' : String(value)
}

function badgeClass(col: GridColumn, value: unknown): string {
  if (!col.badge) return ''
  let map: Record<string, string> = {}
  try { map = JSON.parse(col.badge) } catch { map = {} }
  const variant = map[badgeKey(value)] ?? 'neutral'
  const classes: Record<string, string> = {
    success: 'bg-success-50 text-success-700 ring-success-100',
    warning: 'bg-warning-50 text-warning-700 ring-warning-100',
    danger:  'bg-danger-50 text-danger-700 ring-danger-100',
    info:    'bg-brand-50 text-brand-700 ring-brand-100',
    neutral: 'bg-paper-100 text-ink-600 ring-paper-200',
  }
  return classes[variant] ?? classes.neutral
}

function badgeLabel(col: GridColumn, value: unknown): string {
  const key = badgeKey(value)
  if (!col.badgeLabels) return key
  let labels: Record<string, string> = {}
  try { labels = JSON.parse(col.badgeLabels) } catch { labels = {} }
  return labels[key] ?? key
}

const iconMap: Record<string, unknown> = { Pencil, Eye, Trash2, MoreHorizontal }
const resolveIcon = (name?: string) => name ? (iconMap[name] ?? null) : null

const activeFilterEntries = computed(() =>
    Object.entries(filterValues.value)
        .filter(([, v]) => v !== '' && v != null)
        .map(([field, value]) => {
            const def = props.filters.find(f => f.field === field)
            const label = def?.label ?? field
            let display = String(value)
            if (def?.type === 'select') {
                display = def.options?.find(o => o.value === value)?.label ?? display
            }
            return { field, label, display }
        })
)

function clearFilter(field: string) {
    filterValues.value[field] = ''
    navigate({ page: 1 })
}
</script>

<template>
  <div class="flex flex-col h-full overflow-hidden bg-paper-100">

    <!-- Action Pane -->
    <div class="flex items-center gap-1.5 h-(--action-pane-height) px-6 border-b border-paper-200 bg-white shrink-0 overflow-x-auto">
      <DarejerActions :actions="headerActions" placement="header" />
    </div>

    <!-- Page header — refined hero with subtle gradient -->
    <header class="shrink-0 relative bg-white border-b border-paper-200 overflow-hidden">
      <div
          class="absolute inset-0 opacity-[0.35] pointer-events-none"
          style="
              background-image: radial-gradient(circle at 1px 1px, var(--color-paper-200) 1px, transparent 0);
              background-size: 20px 20px;
          "
      />
      <div class="absolute inset-y-0 inset-e-0 w-2/3 bg-gradient-to-s from-brand-50/60 via-white/0 to-transparent pointer-events-none" />

      <div class="relative flex items-start justify-between gap-6 px-6 pt-5 pb-5">
        <div class="flex items-start gap-3 min-w-0">
          <div class="flex flex-col min-w-0">
            <AppBreadcrumbs class="mb-2" />
            <h1 class="text-[28px] leading-[1.05] tracking-[-0.02em] text-ink-900 font-semibold">
              {{ title }}
            </h1>
            <div class="mt-1.5 flex items-center gap-2">
              <span
                  class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-sm bg-paper-100 ring-1 ring-inset ring-paper-200
                         text-[10.5px] font-bold uppercase tracking-[0.12em] text-ink-600 tabular-nums"
              >
                {{ tableData.total.toLocaleString() }}
                <span class="text-ink-400 font-semibold">{{ __('records') }}</span>
              </span>
              <span v-if="hasSelection" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-sm bg-brand-50 ring-1 ring-inset ring-brand-100 text-[10.5px] font-bold uppercase tracking-[0.12em] text-brand-700 tabular-nums">
                <CheckCircle2 class="w-3 h-3" />
                {{ selected.size }} {{ __('selected') }}
              </span>
            </div>
          </div>
        </div>

        <button
            v-if="filters.length"
            type="button"
            class="flex items-center gap-1.5 h-9 px-3 text-[12.5px] font-semibold border rounded-md
                   shadow-[0_1px_0_rgba(0,0,0,0.02)] transition-all"
            :class="showFilters
                      ? 'bg-brand-50 border-brand-200 text-brand-800 hover:bg-brand-100'
                      : 'bg-white border-paper-300 text-ink-700 hover:bg-paper-75 hover:border-paper-400'"
            @click="showFilters = !showFilters"
        >
          <SlidersHorizontal class="w-3.5 h-3.5" />
          {{ __('Filters') }}
          <span
              v-if="activeFilterCount > 0"
              class="inline-flex items-center justify-center min-w-4 h-4 px-1 rounded-full
                     bg-brand-600 text-white text-[9px] font-bold tabular-nums"
          >
            {{ activeFilterCount }}
          </span>
        </button>
      </div>
    </header>

    <!-- Content -->
    <div class="flex-1 overflow-y-auto px-6 pt-5 pb-6">

      <!-- Active filter chips -->
      <div v-if="activeFilterEntries.length" class="flex flex-wrap items-center gap-2 mb-3">
        <span class="text-[10.5px] font-bold uppercase tracking-[0.12em] text-ink-500">
          {{ __('Active filters') }}
        </span>
        <span
            v-for="entry in activeFilterEntries"
            :key="entry.field"
            class="inline-flex items-center gap-1.5 ps-2 pe-1 h-6 rounded-full bg-white ring-1 ring-inset ring-brand-200
                   text-[11px] font-semibold text-brand-800 shadow-[0_1px_0_rgba(0,0,0,0.02)]"
        >
          <span class="text-[10px] font-bold uppercase tracking-[0.1em] text-brand-500">{{ entry.label }}:</span>
          <span class="text-ink-700 font-medium tabular-nums">{{ entry.display }}</span>
          <button
              type="button"
              class="inline-flex items-center justify-center w-4 h-4 rounded-full hover:bg-brand-100 text-brand-600 transition-colors"
              :title="__('Clear')"
              @click="clearFilter(entry.field)"
          >
            <X class="w-2.5 h-2.5" />
          </button>
        </span>
        <button
            type="button"
            class="text-[11px] font-semibold text-ink-500 hover:text-ink-800 underline-offset-2 hover:underline transition-colors"
            @click="resetFilters"
        >
          {{ __('Clear all') }}
        </button>
      </div>

      <!-- Filter bar -->
      <div
          v-if="showFilters && filters.length"
          class="relative flex flex-wrap items-end gap-3 p-4 mb-4 bg-white border border-paper-200 rounded-md
                 shadow-[0_1px_0_rgba(0,0,0,0.02)]"
      >
        <span class="absolute inset-x-0 top-0 h-0.5 bg-gradient-to-r from-brand-500 via-brand-300 to-transparent rounded-t-md" />
        <div
            v-for="filter in filters"
            :key="filter.field"
            class="flex flex-col gap-1.5 min-w-[10rem]"
        >
          <label class="text-[10.5px] font-bold uppercase tracking-[0.12em] text-ink-500">{{ filter.label }}</label>

          <input
              v-if="filter.type === 'text'"
              v-model="filterValues[filter.field]"
              type="text"
              :placeholder="filter.placeholder ?? ''"
              class="h-9 px-3 text-[13px] border border-paper-300 rounded-md bg-white
                     placeholder:text-ink-400 focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/15 transition-colors"
              @input="onFilterChange"
          />

          <select
              v-else-if="filter.type === 'select'"
              v-model="filterValues[filter.field]"
              class="h-9 px-2.5 text-[13px] border border-paper-300 rounded-md bg-white
                     focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/15 transition-colors"
              @change="onFilterChange"
          >
            <option value="">{{ __('All') }}</option>
            <option v-for="opt in filter.options" :key="opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>

          <input
              v-else-if="filter.type === 'date'"
              v-model="filterValues[filter.field]"
              type="date"
              class="h-9 px-3 text-[13px] border border-paper-300 rounded-md bg-white
                     focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/15 transition-colors"
              @change="onFilterChange"
          />
        </div>

        <button
            v-if="activeFilterCount > 0"
            type="button"
            class="ms-auto flex items-center gap-1.5 h-9 px-3 text-[12.5px] font-semibold text-ink-500 rounded-md
                   hover:text-ink-800 hover:bg-paper-100 transition-colors"
            @click="resetFilters"
        >
          <X class="w-3.5 h-3.5" />
          {{ __('Clear') }} ({{ activeFilterCount }})
        </button>
      </div>

      <!-- Table card -->
      <div class="relative bg-white border border-paper-200 rounded-md overflow-hidden shadow-[0_1px_0_rgba(0,0,0,0.02)]">

        <!-- Bulk-action strip -->
        <div
            v-if="hasBulkActions && hasSelection"
            class="relative flex items-center gap-3 px-4 py-2.5 bg-gradient-to-r from-brand-50 to-brand-50/60 border-b border-brand-100"
        >
          <span class="absolute inset-y-0 start-0 w-0.5 bg-brand-500" />
          <CheckCircle2 class="w-4 h-4 text-brand-600" />
          <span class="text-[12px] font-bold text-brand-800 tabular-nums">
            {{ __(':count selected', { count: selected.size }) }}
          </span>
          <button
              type="button"
              class="text-[11.5px] font-semibold text-brand-700 hover:text-brand-900 underline-offset-2 hover:underline transition-colors"
              @click="clearSelection"
          >
            {{ __('Clear') }}
          </button>
          <div class="ms-auto">
            <DarejerActions
                :actions="bulkActions ?? []"
                :selected="selectedIds"
                :on-bulk-success="clearSelection"
                placement="header"
            />
          </div>
        </div>

        <!-- Table toolbar -->
        <div class="flex items-center gap-3 px-3 py-2.5 bg-gradient-to-b from-paper-75 to-white border-b border-paper-200">
          <div class="relative max-w-xs flex-1">
            <Search class="absolute start-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-ink-400 pointer-events-none" />
            <input
                v-model="globalSearch"
                type="search"
                :placeholder="__('Search…')"
                class="w-full h-8 ps-9 pe-3 text-[13px] bg-white border border-paper-300 rounded-md
                       placeholder:text-ink-400 focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/15 transition-colors"
            />
          </div>
          <span class="ms-auto inline-flex items-center gap-1.5 text-[11px] text-ink-500 tabular-nums font-semibold">
            <span class="text-ink-700">{{ tableData.from }}–{{ tableData.to }}</span>
            <span class="text-ink-300">/</span>
            <span>{{ tableData.total.toLocaleString() }}</span>
          </span>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
          <table class="w-full border-collapse">
            <thead>
              <tr class="bg-paper-75 border-b border-paper-200 sticky top-0 z-10">
                <th v-if="selectable" class="w-10 px-3 h-10">
                  <input
                      type="checkbox"
                      :checked="allSelected"
                      :indeterminate.prop="someSelected"
                      class="w-3.5 h-3.5 rounded-sm accent-brand-600 cursor-pointer align-middle"
                      @change="toggleAll"
                  />
                </th>
                <th
                    v-for="col in visibleColumns"
                    :key="col.field"
                    class="px-3 h-10 text-start whitespace-nowrap"
                    :class="[
                        col.sortable ? 'cursor-pointer hover:bg-paper-100 select-none transition-colors' : '',
                        col.align === 'right'  ? 'text-end'  : '',
                        col.align === 'center' ? 'text-center' : '',
                    ]"
                    :style="col.width ? { width: col.width } : {}"
                    @click="toggleSort(col.field, col.sortable)"
                >
                  <div
                      class="flex items-center gap-1.5"
                      :class="col.align === 'right' ? 'justify-end' : col.align === 'center' ? 'justify-center' : ''"
                  >
                    <span
                        class="text-[10.5px] font-bold uppercase tracking-[0.12em]"
                        :class="sortField === col.field ? 'text-brand-700' : 'text-ink-500'"
                    >
                      {{ col.label }}
                    </span>
                    <template v-if="col.sortable">
                      <ChevronUp      v-if="sortField === col.field && sortOrder === 'asc'"   class="w-3 h-3 text-brand-600" />
                      <ChevronDown    v-else-if="sortField === col.field && sortOrder === 'desc'" class="w-3 h-3 text-brand-600" />
                      <ChevronsUpDown v-else class="w-3 h-3 text-ink-300" />
                    </template>
                  </div>
                </th>
                <th v-if="rowActions.length" class="w-16 px-3 h-10" />
              </tr>
            </thead>

            <tbody>
              <tr v-if="tableData.data.length === 0">
                <td
                    :colspan="visibleColumns.length + (selectable ? 1 : 0) + (rowActions.length ? 1 : 0)"
                    class="px-3 py-16"
                >
                  <div class="relative flex flex-col items-center gap-3 text-center">
                    <div
                        class="absolute inset-0 -m-6 opacity-[0.5] pointer-events-none rounded-md"
                        style="
                            background-image: radial-gradient(circle at 1px 1px, var(--color-paper-200) 1px, transparent 0);
                            background-size: 16px 16px;
                            mask-image: radial-gradient(ellipse at center, black 0%, transparent 70%);
                        "
                    />
                    <div
                        class="relative inline-flex items-center justify-center w-14 h-14 rounded-md
                               bg-gradient-to-br from-paper-75 to-paper-100 ring-1 ring-paper-200
                               shadow-[0_2px_6px_-2px_rgba(0,0,0,0.05)]"
                    >
                      <Inbox class="w-6 h-6 text-ink-400" />
                    </div>
                    <div class="relative flex flex-col gap-1">
                      <span class="text-[14px] font-semibold text-ink-800 tracking-tight">
                        {{ emptyMessage ?? __('No records found.') }}
                      </span>
                      <span class="text-[12px] text-ink-500 max-w-sm">
                        {{ __('Adjust your filters or search to see results.') }}
                      </span>
                    </div>
                  </div>
                </td>
              </tr>

              <tr
                  v-for="row in tableData.data"
                  :key="String(row.id ?? row)"
                  class="group/row relative border-b border-paper-100 last:border-b-0 transition-colors duration-75"
                  :class="selected.has(row.id ?? row)
                            ? 'bg-brand-50/70 hover:bg-brand-50'
                            : 'hover:bg-paper-75'"
              >
                <!-- Leading rail on hover/selection -->
                <td
                    v-if="selectable"
                    class="relative px-3 h-10"
                >
                  <span
                      class="absolute inset-y-0 start-0 w-0.5 transition-opacity"
                      :class="selected.has(row.id ?? row)
                                ? 'bg-brand-500 opacity-100'
                                : 'bg-brand-500 opacity-0 group-hover/row:opacity-60'"
                  />
                  <input
                      type="checkbox"
                      :checked="selected.has(row.id ?? row)"
                      class="w-3.5 h-3.5 rounded-sm accent-brand-600 cursor-pointer align-middle"
                      @change="toggleRow(row.id ?? row)"
                  />
                </td>

                <td
                    v-for="(col, ci) in visibleColumns"
                    :key="col.field"
                    class="px-3 h-10 text-[13px] text-ink-800 relative"
                    :class="[
                        col.align === 'right'  ? 'text-end tabular-nums'  : '',
                        col.align === 'center' ? 'text-center'              : '',
                        ci === 0 && !selectable ? '' : '',
                    ]"
                >
                  <!-- If no selectable, the first column gets the rail -->
                  <span
                      v-if="!selectable && ci === 0"
                      class="absolute inset-y-0 start-0 w-0.5 bg-brand-500 opacity-0 group-hover/row:opacity-60 transition-opacity"
                  />
                  <span
                      v-if="col.badge"
                      class="inline-flex items-center px-1.5 py-0.5 rounded-sm
                             text-[10px] font-bold uppercase tracking-[0.04em] ring-1 ring-inset"
                      :class="badgeClass(col, row[col.field])"
                  >
                    {{ badgeLabel(col, row[col.field]) }}
                  </span>
                  <span
                      v-else
                      class="block truncate max-w-xs"
                      :class="ci === 0 ? 'font-medium text-ink-900' : ''"
                  >
                    {{ formatCell(row[col.field], col) ?? '—' }}
                  </span>
                </td>

                <td v-if="rowActions.length" class="px-2 h-10">
                  <div class="flex items-center justify-end gap-0.5">
                    <template v-if="(rowActionsDisplay ?? 'inline') === 'dropdown'">
                      <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                          <button type="button" class="flex items-center justify-center w-8 h-8 rounded-md text-ink-400 hover:text-brand-700 hover:bg-brand-50 transition-colors opacity-60 group-hover/row:opacity-100">
                            <MoreHorizontal class="w-4 h-4" />
                          </button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-40">
                          <DropdownMenuItem
                              v-for="action in rowActions"
                              :key="action.label"
                              class="flex items-center gap-2 text-sm cursor-pointer"
                              :class="action.variant === 'destructive' ? 'text-danger-700 focus:text-danger-700' : ''"
                              @click="handleRowAction(action, row)"
                          >
                            <component :is="resolveIcon(action.icon)" v-if="action.icon" class="w-3.5 h-3.5 shrink-0" />
                            {{ action.label }}
                          </DropdownMenuItem>
                        </DropdownMenuContent>
                      </DropdownMenu>
                    </template>
                    <template v-else>
                      <TooltipProvider :delay-duration="0">
                        <Tooltip
                            v-for="action in rowActions"
                            :key="action.label"
                        >
                          <TooltipTrigger as-child>
                            <button
                                type="button"
                                class="flex items-center justify-center w-8 h-8 rounded-md transition-colors"
                                :class="action.variant === 'destructive'
                                    ? 'text-ink-400 hover:text-danger-700 hover:bg-danger-50'
                                    : 'text-ink-400 hover:text-brand-700 hover:bg-brand-50'"
                                :aria-label="__(action.label)"
                                @click="handleRowAction(action, row)"
                            >
                              <component :is="resolveIcon(action.icon)" v-if="action.icon" class="w-3.5 h-3.5" />
                              <span v-else class="text-[11px] font-semibold">{{ __(action.label) }}</span>
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
            class="flex items-center justify-between px-4 py-3 bg-gradient-to-b from-white to-paper-75 border-t border-paper-200"
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
                class="flex items-center justify-center w-8 h-8 rounded-md border border-paper-300 bg-white text-ink-500 hover:bg-brand-50 hover:text-brand-700 hover:border-brand-200 disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-white disabled:hover:text-ink-500 disabled:hover:border-paper-300 transition-colors rtl:rotate-180"
                @click="goToPage(tableData.current_page - 1)"
            >
              <ChevronLeft class="w-3.5 h-3.5" />
            </button>
            <template v-for="(p, idx) in pages" :key="`${idx}-${p}`">
              <span v-if="p === '...'" class="flex items-center justify-center w-8 h-8 text-xs text-ink-300">…</span>
              <button
                  v-else
                  type="button"
                  class="flex items-center justify-center w-8 h-8 rounded-md text-[12px] font-semibold border tabular-nums transition-all"
                  :class="tableData.current_page === p
                            ? 'bg-gradient-to-b from-brand-500 to-brand-600 text-white border-brand-600 shadow-[0_1px_2px_rgba(0,120,212,0.4)]'
                            : 'bg-white border-paper-300 text-ink-600 hover:bg-brand-50 hover:text-brand-700 hover:border-brand-200'"
                  @click="goToPage(p as number)"
              >
                {{ p }}
              </button>
            </template>
            <button
                type="button"
                :disabled="tableData.current_page >= tableData.last_page"
                class="flex items-center justify-center w-8 h-8 rounded-md border border-paper-300 bg-white text-ink-500 hover:bg-brand-50 hover:text-brand-700 hover:border-brand-200 disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-white disabled:hover:text-ink-500 disabled:hover:border-paper-300 transition-colors rtl:rotate-180"
                @click="goToPage(tableData.current_page + 1)"
            >
              <ChevronRight class="w-3.5 h-3.5" />
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Confirm dialog -->
  <Dialog :open="confirmOpen" @update:open="confirmOpen = $event">
    <DialogContent class="max-w-sm p-0 overflow-hidden">
      <DialogHeader class="px-5 py-4 border-b border-paper-200 bg-gradient-to-b from-paper-75 to-white">
        <DialogTitle class="text-base font-semibold text-ink-900 tracking-tight">{{ __('Confirm') }}</DialogTitle>
      </DialogHeader>
      <div class="px-5 py-5">
        <DialogDescription class="text-sm text-ink-700 leading-relaxed">{{ confirmMsg }}</DialogDescription>
      </div>
      <DialogFooter class="flex justify-end gap-2 px-5 py-3 border-t border-paper-200 bg-paper-75">
        <Button variant="outline" class="h-9 text-sm" @click="confirmOpen = false">{{ __('Cancel') }}</Button>
        <Button
            class="h-9 text-sm bg-danger-600 hover:bg-danger-700 text-white border-transparent"
            @click="executeConfirmed"
        >
          {{ confirmAction?.label }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
