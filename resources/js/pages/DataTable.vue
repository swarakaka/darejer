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
} from 'lucide-vue-next'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
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

defineOptions({ layout: AppLayout })

interface GridColumn {
    field:       string
    label:       string
    sortable?:   boolean
    searchable?: boolean
    width?:      string
    hidden?:     boolean
    align?:      'left' | 'center' | 'right'
    badge?:      string
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

function badgeClass(col: GridColumn, value: unknown): string {
    if (!col.badge) return ''
    let map: Record<string, string> = {}
    try { map = JSON.parse(col.badge) } catch { map = {} }
    const variant = map[String(value)] ?? 'neutral'
    const classes: Record<string, string> = {
        success: 'bg-success-50 text-success-700 border-success-100',
        warning: 'bg-warning-50 text-warning-700 border-warning-100',
        danger:  'bg-danger-50 text-danger-700 border-danger-100',
        info:    'bg-brand-50 text-brand-700 border-brand-100',
        neutral: 'bg-paper-100 text-ink-500 border-paper-200',
    }
    return classes[variant] ?? classes.neutral
}

const iconMap: Record<string, unknown> = { Pencil, Eye, Trash2, MoreHorizontal }
const resolveIcon = (name?: string) => name ? (iconMap[name] ?? null) : null
</script>

<template>
    <div class="flex flex-col h-full overflow-hidden bg-paper-50">

        <!-- Action Pane -->
        <div
            class="flex items-center gap-1.5 px-6 border-b border-paper-200 bg-paper-75 shrink-0 overflow-x-auto"
            :style="{ height: 'var(--action-pane-height)' }"
        >
            <DarejerActions :actions="headerActions" placement="header" />
        </div>

        <!-- Page title -->
        <div class="flex items-start justify-between gap-6 px-6 pt-5 pb-4 border-b border-paper-200 shrink-0">
            <div class="flex flex-col min-w-0">
                <AppBreadcrumbs class="mb-3" />
                <h1 class="font-serif text-[1.75rem] leading-[1.1] tracking-tight text-ink-900">
                    {{ title }}
                </h1>
            </div>

            <button
                v-if="filters.length"
                type="button"
                class="flex items-center gap-1.5 h-8 px-3 text-sm border border-paper-300 rounded-sm
                       text-ink-600 hover:bg-paper-100 transition-colors"
                @click="showFilters = !showFilters"
            >
                <SlidersHorizontal class="w-3.5 h-3.5" />
                Filters
                <span
                    v-if="activeFilterCount > 0"
                    class="inline-flex items-center justify-center w-4 h-4 rounded-full
                           bg-brand-600 text-white text-[9px] font-bold tabular-nums"
                >
                    {{ activeFilterCount }}
                </span>
            </button>
        </div>

        <!-- Content -->
        <div class="flex-1 overflow-y-auto px-6 pt-4 pb-6">

            <!-- Filter bar -->
            <div
                v-if="showFilters && filters.length"
                class="flex flex-wrap items-end gap-3 p-3 bg-paper-75 border border-paper-200 rounded-md mb-3"
            >
                <div
                    v-for="filter in filters"
                    :key="filter.field"
                    class="flex flex-col gap-1 min-w-[10rem]"
                >
                    <label class="text-xs font-medium text-ink-500">{{ filter.label }}</label>

                    <input
                        v-if="filter.type === 'text'"
                        v-model="filterValues[filter.field]"
                        type="text"
                        :placeholder="filter.placeholder ?? ''"
                        class="h-8 px-2.5 text-sm border border-paper-300 rounded-sm bg-white
                               placeholder:text-ink-400 focus:outline-none focus:border-brand-500 transition-colors"
                        @input="onFilterChange"
                    />

                    <select
                        v-else-if="filter.type === 'select'"
                        v-model="filterValues[filter.field]"
                        class="h-8 px-2 text-sm border border-paper-300 rounded-sm bg-white
                               focus:outline-none focus:border-brand-500 transition-colors"
                        @change="onFilterChange"
                    >
                        <option value="">All</option>
                        <option v-for="opt in filter.options" :key="opt.value" :value="opt.value">
                            {{ opt.label }}
                        </option>
                    </select>

                    <input
                        v-else-if="filter.type === 'date'"
                        v-model="filterValues[filter.field]"
                        type="date"
                        class="h-8 px-2.5 text-sm border border-paper-300 rounded-sm bg-white
                               focus:outline-none focus:border-brand-500 transition-colors"
                        @change="onFilterChange"
                    />
                </div>

                <button
                    v-if="activeFilterCount > 0"
                    type="button"
                    class="flex items-center gap-1.5 h-8 px-3 text-sm text-ink-500
                           hover:text-ink-800 transition-colors"
                    @click="resetFilters"
                >
                    <X class="w-3.5 h-3.5" />
                    Clear ({{ activeFilterCount }})
                </button>
            </div>

            <!-- Table card -->
            <div class="border border-paper-200 rounded-md overflow-hidden bg-white">

                <!-- Bulk-action strip: visible only while rows are selected -->
                <div
                    v-if="hasBulkActions && hasSelection"
                    class="flex items-center gap-2 px-3 py-2 bg-brand-50 border-b border-brand-100"
                >
                    <span class="text-xs font-medium text-brand-700 tabular-nums">
                        {{ selected.size }} selected
                    </span>
                    <button
                        type="button"
                        class="text-xs text-ink-500 hover:text-ink-800 underline-offset-2 hover:underline"
                        @click="clearSelection"
                    >
                        Clear
                    </button>
                    <div class="ml-auto">
                        <DarejerActions
                            :actions="bulkActions ?? []"
                            :selected="selectedIds"
                            :on-bulk-success="clearSelection"
                            placement="header"
                        />
                    </div>
                </div>

                <!-- Table toolbar -->
                <div class="flex items-center gap-2 px-3 py-2 bg-paper-75 border-b border-paper-200">
                    <div class="relative max-w-xs flex-1">
                        <Search class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-ink-400" />
                        <input
                            v-model="globalSearch"
                            type="search"
                            placeholder="Search…"
                            class="w-full h-8 pl-8 pr-3 text-sm bg-white border border-paper-300 rounded-sm
                                   placeholder:text-ink-400 focus:outline-none focus:border-brand-500 transition-colors"
                        />
                    </div>
                    <span class="ml-auto text-xs text-ink-400 tabular-nums">
                        {{ tableData.from }}–{{ tableData.to }} of {{ tableData.total }}
                    </span>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-paper-75 border-b border-paper-200">
                                <th v-if="selectable" class="w-9 px-3 h-9">
                                    <input
                                        type="checkbox"
                                        :checked="allSelected"
                                        :indeterminate.prop="someSelected"
                                        class="w-4 h-4 rounded-sm accent-brand-600 cursor-pointer"
                                        @change="toggleAll"
                                    />
                                </th>
                                <th
                                    v-for="col in visibleColumns"
                                    :key="col.field"
                                    class="px-3 h-9 text-left whitespace-nowrap"
                                    :class="[
                                        col.sortable ? 'cursor-pointer hover:bg-paper-100 select-none transition-colors' : '',
                                        col.align === 'right'  ? 'text-right'  : '',
                                        col.align === 'center' ? 'text-center' : '',
                                    ]"
                                    :style="col.width ? { width: col.width } : {}"
                                    @click="toggleSort(col.field, col.sortable)"
                                >
                                    <div
                                        class="flex items-center gap-1"
                                        :class="col.align === 'right' ? 'justify-end' : col.align === 'center' ? 'justify-center' : ''"
                                    >
                                        <span class="text-[10px] font-semibold uppercase tracking-[0.08em] text-ink-400">
                                            {{ col.label }}
                                        </span>
                                        <template v-if="col.sortable">
                                            <ChevronUp      v-if="sortField === col.field && sortOrder === 'asc'"   class="w-3 h-3 text-brand-600" />
                                            <ChevronDown    v-else-if="sortField === col.field && sortOrder === 'desc'" class="w-3 h-3 text-brand-600" />
                                            <ChevronsUpDown v-else class="w-3 h-3 text-ink-300" />
                                        </template>
                                    </div>
                                </th>
                                <th v-if="rowActions.length" class="w-16 px-3 h-9" />
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-if="tableData.data.length === 0">
                                <td
                                    :colspan="visibleColumns.length + (selectable ? 1 : 0) + (rowActions.length ? 1 : 0)"
                                    class="px-3 py-10 text-center"
                                >
                                    <div class="flex flex-col items-center gap-2 text-ink-400">
                                        <Inbox class="w-8 h-8" />
                                        <span class="text-sm">{{ emptyMessage ?? 'No records found.' }}</span>
                                    </div>
                                </td>
                            </tr>

                            <tr
                                v-for="row in tableData.data"
                                :key="String(row.id ?? row)"
                                class="border-b border-paper-100 hover:bg-paper-50 transition-colors duration-75"
                                :class="selected.has(row.id ?? row) ? 'bg-brand-50' : ''"
                            >
                                <td v-if="selectable" class="px-3 h-9">
                                    <input
                                        type="checkbox"
                                        :checked="selected.has(row.id ?? row)"
                                        class="w-4 h-4 rounded-sm accent-brand-600 cursor-pointer"
                                        @change="toggleRow(row.id ?? row)"
                                    />
                                </td>

                                <td
                                    v-for="col in visibleColumns"
                                    :key="col.field"
                                    class="px-3 h-9 text-sm text-ink-800"
                                    :class="[
                                        col.align === 'right'  ? 'text-right tabular-nums'  : '',
                                        col.align === 'center' ? 'text-center'              : '',
                                    ]"
                                >
                                    <span
                                        v-if="col.badge"
                                        class="inline-flex items-center px-1.5 py-0.5 rounded-sm
                                               text-[10px] font-semibold uppercase tracking-wide border"
                                        :class="badgeClass(col, row[col.field])"
                                    >
                                        {{ row[col.field] }}
                                    </span>
                                    <span v-else class="block truncate max-w-xs">
                                        {{ row[col.field] ?? '—' }}
                                    </span>
                                </td>

                                <td v-if="rowActions.length" class="px-2 h-9">
                                    <div class="flex items-center justify-end gap-0.5">
                                        <template v-if="rowActions.length === 1">
                                            <button
                                                type="button"
                                                class="flex items-center justify-center w-7 h-7 rounded-sm
                                                       text-ink-400 hover:text-ink-700 hover:bg-paper-100 transition-colors"
                                                :title="rowActions[0].label"
                                                @click="handleRowAction(rowActions[0], row)"
                                            >
                                                <component :is="resolveIcon(rowActions[0].icon)" v-if="rowActions[0].icon" class="w-3.5 h-3.5" />
                                                <span v-else class="text-xs">{{ rowActions[0].label }}</span>
                                            </button>
                                        </template>
                                        <template v-else>
                                            <DropdownMenu>
                                                <DropdownMenuTrigger as-child>
                                                    <button type="button" class="flex items-center justify-center w-7 h-7 rounded-sm text-ink-400 hover:text-ink-700 hover:bg-paper-100 transition-colors">
                                                        <MoreHorizontal class="w-4 h-4" />
                                                    </button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="end" class="w-36">
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
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div
                    v-if="tableData.total > tableData.per_page"
                    class="flex items-center justify-between px-3 py-2 bg-paper-75 border-t border-paper-200"
                >
                    <span class="text-xs text-ink-400 tabular-nums">
                        Showing {{ tableData.from }}–{{ tableData.to }} of {{ tableData.total }} records
                    </span>
                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            :disabled="tableData.current_page <= 1"
                            class="flex items-center justify-center w-7 h-7 rounded-sm border border-paper-300 text-ink-500 hover:bg-paper-100 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                            @click="goToPage(tableData.current_page - 1)"
                        >
                            <ChevronLeft class="w-3.5 h-3.5" />
                        </button>
                        <template v-for="(p, idx) in pages" :key="`${idx}-${p}`">
                            <span v-if="p === '...'" class="flex items-center justify-center w-7 h-7 text-xs text-ink-300">…</span>
                            <button
                                v-else
                                type="button"
                                class="flex items-center justify-center w-7 h-7 rounded-sm text-xs border tabular-nums transition-colors"
                                :class="tableData.current_page === p
                                    ? 'bg-brand-600 text-white border-brand-600'
                                    : 'border-paper-300 text-ink-600 hover:bg-paper-100'"
                                @click="goToPage(p as number)"
                            >
                                {{ p }}
                            </button>
                        </template>
                        <button
                            type="button"
                            :disabled="tableData.current_page >= tableData.last_page"
                            class="flex items-center justify-center w-7 h-7 rounded-sm border border-paper-300 text-ink-500 hover:bg-paper-100 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
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
            <DialogHeader class="px-4 py-3 border-b border-paper-200 bg-paper-75">
                <DialogTitle class="font-serif text-lg">Confirm</DialogTitle>
            </DialogHeader>
            <div class="px-4 py-4">
                <DialogDescription class="text-sm text-ink-600">{{ confirmMsg }}</DialogDescription>
            </div>
            <DialogFooter class="flex justify-end gap-2 px-4 py-3 border-t border-paper-200 bg-paper-75">
                <Button variant="outline" class="h-8 text-sm" @click="confirmOpen = false">Cancel</Button>
                <Button
                    class="h-8 text-sm bg-danger-600 hover:bg-danger-700 text-white border-transparent"
                    @click="executeConfirmed"
                >
                    {{ confirmAction?.label }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
