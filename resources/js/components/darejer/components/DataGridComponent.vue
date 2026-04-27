<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { useDataUrl } from '@/composables/useDataUrl'
import { Button }   from '@/components/ui/button'
import {
    ChevronUp, ChevronDown, ChevronsUpDown,
    ChevronLeft, ChevronRight,
    Search, Loader2, Inbox,
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
import useTranslation from '@/composables/useTranslation'
import type { DarejerComponent } from '@/types/darejer'

const { __ } = useTranslation()

const props = defineProps<{
    component: DarejerComponent
    record:    Record<string, unknown>
    errors:    Record<string, string>
    formData?: Record<string, unknown>
}>()

interface GridColumn {
    field:      string
    label:      string
    sortable?:  boolean
    searchable?: boolean
    width?:     string
    hidden?:    boolean
    align?:     'left' | 'center' | 'right'
    badge?:     string
}

interface GridRowAction {
    label:      string
    icon?:      string
    type:       string
    urlPattern?: string
    dialog?:    boolean
    confirm?:   string
    variant:    string
}

const rows        = ref<Record<string, unknown>[]>([])
const total       = ref(0)
const currentPage = ref(1)
const lastPage    = ref(1)
const from        = ref(0)
const to          = ref(0)

const sortField    = ref<string>((props.component.defaultSort as string) ?? '')
const sortOrder    = ref<'asc' | 'desc'>((props.component.defaultOrder as 'asc' | 'desc') ?? 'asc')
const globalSearch = ref('')
const filters      = ref<Record<string, string>>({})
const selected     = ref<Set<unknown>>(new Set())

const confirmOpen   = ref(false)
const confirmMsg    = ref('')
const confirmAction = ref<GridRowAction | null>(null)
const confirmRow    = ref<Record<string, unknown> | null>(null)

const columns = computed((): GridColumn[] =>
    ((props.component.gridColumns as GridColumn[]) ?? []).filter(c => !c.hidden)
)

const rowActions = computed((): GridRowAction[] =>
    (props.component.rowActions as GridRowAction[]) ?? []
)

const perPage      = computed(() => (props.component.perPage as number) ?? 15)
const isSelectable = computed(() => !!props.component.selectable)
const emptyMsg     = computed(() => (props.component.emptyMessage as string) ?? __('No records found.'))

const allSelected = computed(() =>
    rows.value.length > 0 && rows.value.every(r => selected.value.has(r.id ?? r))
)

const someSelected = computed(() =>
    !allSelected.value && rows.value.some(r => selected.value.has(r.id ?? r))
)

const { load, http } = useDataUrl<Record<string, unknown>>(
    props.component.dataUrl as string | undefined,
    { perPage: perPage.value },
)

async function fetchData() {
    const result = await load({
        page:    currentPage.value,
        perPage: perPage.value,
        sort:    sortField.value || undefined,
        order:   sortField.value ? sortOrder.value : undefined,
        search:  globalSearch.value || undefined,
        filters: filters.value,
    })

    if (!result) return

    rows.value        = result.data
    total.value       = result.total
    currentPage.value = result.current_page
    lastPage.value    = result.last_page
    from.value        = result.from
    to.value          = result.to
}

onMounted(() => fetchData())

let searchTimer: ReturnType<typeof setTimeout>
watch(globalSearch, () => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(() => {
        currentPage.value = 1
        fetchData()
    }, 300)
})

watch([sortField, sortOrder, currentPage], () => fetchData())

function toggleSort(field: string, sortable?: boolean) {
    if (!sortable) return
    if (sortField.value === field) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
    } else {
        sortField.value = field
        sortOrder.value = 'asc'
    }
    currentPage.value = 1
}

function toggleRow(id: unknown) {
    if (selected.value.has(id)) {
        selected.value.delete(id)
    } else {
        selected.value.add(id)
    }
    selected.value = new Set(selected.value)
}

function toggleAll() {
    if (allSelected.value) {
        selected.value = new Set()
    } else {
        selected.value = new Set(rows.value.map(r => r.id ?? r))
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
    executeRowAction(action, row)
}

function executeRowAction(action: GridRowAction, row: Record<string, unknown>) {
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
        executeRowAction(confirmAction.value, confirmRow.value)
    }
    confirmOpen.value = false
}

function badgeClass(column: GridColumn, value: unknown): string {
    if (!column.badge) return ''
    let map: Record<string, string> = {}
    try { map = JSON.parse(column.badge) } catch { map = {} }
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

const iconMap: Record<string, unknown> = {
    Pencil, Eye, Trash2, MoreHorizontal,
}

function resolveIcon(name?: string) {
    return name ? (iconMap[name] ?? null) : null
}

const pages = computed(() => {
    const totalPages = lastPage.value
    const cur        = currentPage.value
    const delta      = 2
    const range: (number | '...')[] = []

    for (let i = Math.max(1, cur - delta); i <= Math.min(totalPages, cur + delta); i++) {
        range.push(i)
    }

    if (range[0] !== 1) {
        range.unshift('...')
        range.unshift(1)
    }

    if (range[range.length - 1] !== totalPages) {
        range.push('...')
        range.push(totalPages)
    }

    return range
})
</script>

<template>
    <div class="flex flex-col border border-paper-200 rounded-md overflow-hidden bg-white col-span-full">

        <!-- Toolbar -->
        <div class="flex items-center gap-2 px-3 py-2 bg-paper-75 border-b border-paper-200">

            <div v-if="component.searchable !== false" class="relative flex-1 max-w-xs">
                <Search class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-ink-400" />
                <input
                    v-model="globalSearch"
                    type="search"
                    :placeholder="__('Search…')"
                    class="w-full h-8 pl-8 pr-3 text-sm bg-white border border-paper-300 rounded-sm
                           placeholder:text-ink-400 focus:outline-none focus:border-brand-500
                           transition-colors duration-100"
                />
            </div>

            <div class="ml-auto flex items-center gap-2 text-xs text-ink-400 tabular-nums">
                <span v-if="total > 0">{{ __(':from–:to of :total', { from, to, total }) }}</span>
                <Loader2 v-if="http.processing" class="w-3.5 h-3.5 animate-spin text-brand-500" />
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full border-collapse">

                <thead>
                    <tr class="bg-paper-75 border-b border-paper-200">

                        <th v-if="isSelectable" class="w-9 px-3 h-9">
                            <input
                                type="checkbox"
                                :checked="allSelected"
                                :indeterminate.prop="someSelected"
                                class="w-4 h-4 rounded-sm accent-brand-600 cursor-pointer"
                                @change="toggleAll"
                            />
                        </th>

                        <th
                            v-for="col in columns"
                            :key="col.field"
                            class="px-3 h-9 text-left whitespace-nowrap select-none"
                            :class="[
                                col.sortable ? 'cursor-pointer hover:bg-paper-100 transition-colors' : '',
                                col.align === 'center' ? 'text-center' : '',
                                col.align === 'right'  ? 'text-right'  : '',
                            ]"
                            :style="col.width ? { width: col.width } : {}"
                            @click="toggleSort(col.field, col.sortable)"
                        >
                            <div
                                class="flex items-center gap-1"
                                :class="col.align === 'center' ? 'justify-center' : col.align === 'right' ? 'justify-end' : ''"
                            >
                                <span class="text-[10px] font-semibold uppercase tracking-[0.08em] text-ink-400">
                                    {{ col.label }}
                                </span>
                                <template v-if="col.sortable">
                                    <ChevronUp
                                        v-if="sortField === col.field && sortOrder === 'asc'"
                                        class="w-3 h-3 text-brand-600"
                                    />
                                    <ChevronDown
                                        v-else-if="sortField === col.field && sortOrder === 'desc'"
                                        class="w-3 h-3 text-brand-600"
                                    />
                                    <ChevronsUpDown
                                        v-else
                                        class="w-3 h-3 text-ink-300"
                                    />
                                </template>
                            </div>
                        </th>

                        <th v-if="rowActions.length" class="w-16 px-3 h-9" />

                    </tr>
                </thead>

                <tbody>

                    <!-- Loading skeleton -->
                    <template v-if="http.processing && rows.length === 0">
                        <tr v-for="i in perPage" :key="i" class="border-b border-paper-100">
                            <td v-if="isSelectable" class="px-3 h-9">
                                <div class="w-4 h-4 bg-paper-100 rounded-sm animate-pulse" />
                            </td>
                            <td
                                v-for="col in columns"
                                :key="col.field"
                                class="px-3 h-9"
                            >
                                <div
                                    class="h-3 bg-paper-100 rounded-sm animate-pulse"
                                    :style="{ width: Math.random() * 40 + 40 + '%' }"
                                />
                            </td>
                            <td v-if="rowActions.length" class="px-3 h-9" />
                        </tr>
                    </template>

                    <!-- Empty state -->
                    <template v-else-if="!http.processing && rows.length === 0">
                        <tr>
                            <td
                                :colspan="columns.length + (isSelectable ? 1 : 0) + (rowActions.length ? 1 : 0)"
                                class="px-3 py-10 text-center"
                            >
                                <div class="flex flex-col items-center gap-2 text-ink-400">
                                    <Inbox class="w-8 h-8" />
                                    <span class="text-sm">{{ emptyMsg }}</span>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <!-- Data rows -->
                    <template v-else>
                        <tr
                            v-for="row in rows"
                            :key="String(row.id ?? row)"
                            class="border-b border-paper-100 hover:bg-paper-50 transition-colors duration-75"
                            :class="selected.has(row.id ?? row) ? 'bg-brand-50' : ''"
                        >
                            <td v-if="isSelectable" class="px-3 h-9">
                                <input
                                    type="checkbox"
                                    :checked="selected.has(row.id ?? row)"
                                    class="w-4 h-4 rounded-sm accent-brand-600 cursor-pointer"
                                    @change="toggleRow(row.id ?? row)"
                                />
                            </td>

                            <td
                                v-for="col in columns"
                                :key="col.field"
                                class="px-3 h-9 text-sm text-ink-800"
                                :class="[
                                    col.align === 'center' ? 'text-center' : '',
                                    col.align === 'right'  ? 'text-right tabular-nums'  : '',
                                ]"
                            >
                                <span
                                    v-if="col.badge"
                                    class="inline-flex items-center px-1.5 py-0.5 rounded-sm text-[10px] font-semibold uppercase tracking-wide border"
                                    :class="badgeClass(col, row[col.field])"
                                >
                                    {{ row[col.field] }}
                                </span>

                                <span v-else class="truncate block max-w-xs">
                                    {{ row[col.field] ?? '—' }}
                                </span>
                            </td>

                            <td v-if="rowActions.length" class="px-2 h-9">
                                <div class="flex items-center justify-end gap-0.5">

                                    <!-- Single action: show inline -->
                                    <template v-if="rowActions.length === 1">
                                        <button
                                            type="button"
                                            class="flex items-center justify-center w-7 h-7 rounded-sm
                                                   text-ink-400 hover:text-ink-700 hover:bg-paper-100
                                                   transition-colors duration-100"
                                            :title="rowActions[0].label"
                                            @click="handleRowAction(rowActions[0], row)"
                                        >
                                            <component
                                                :is="resolveIcon(rowActions[0].icon)"
                                                v-if="rowActions[0].icon"
                                                class="w-3.5 h-3.5"
                                            />
                                            <span v-else class="text-xs">{{ rowActions[0].label }}</span>
                                        </button>
                                    </template>

                                    <!-- Multiple actions: dropdown -->
                                    <template v-else>
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <button
                                                    type="button"
                                                    class="flex items-center justify-center w-7 h-7 rounded-sm
                                                           text-ink-400 hover:text-ink-700 hover:bg-paper-100
                                                           transition-colors duration-100"
                                                >
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
                                                    <component
                                                        :is="resolveIcon(action.icon)"
                                                        v-if="action.icon"
                                                        class="w-3.5 h-3.5 shrink-0"
                                                    />
                                                    {{ action.label }}
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </template>

                                </div>
                            </td>

                        </tr>
                    </template>

                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div
            v-if="total > perPage"
            class="flex items-center justify-between px-3 py-2 bg-paper-75 border-t border-paper-200"
        >
            <span class="text-xs text-ink-400 tabular-nums">
                {{ __('Showing :from–:to of :total records', { from, to, total }) }}
            </span>

            <div class="flex items-center gap-1">
                <button
                    type="button"
                    :disabled="currentPage <= 1"
                    class="flex items-center justify-center w-7 h-7 rounded-sm border border-paper-300
                           text-ink-500 hover:bg-paper-100 disabled:opacity-40 disabled:cursor-not-allowed
                           transition-colors duration-100"
                    @click="currentPage--"
                >
                    <ChevronLeft class="w-3.5 h-3.5" />
                </button>

                <template v-for="(p, idx) in pages" :key="`${idx}-${p}`">
                    <span
                        v-if="p === '...'"
                        class="flex items-center justify-center w-7 h-7 text-xs text-ink-300"
                    >
                        …
                    </span>
                    <button
                        v-else
                        type="button"
                        class="flex items-center justify-center w-7 h-7 rounded-sm text-xs border tabular-nums transition-colors duration-100"
                        :class="currentPage === p
                            ? 'bg-brand-600 text-white border-brand-600'
                            : 'border-paper-300 text-ink-600 hover:bg-paper-100'"
                        @click="currentPage = p as number"
                    >
                        {{ p }}
                    </button>
                </template>

                <button
                    type="button"
                    :disabled="currentPage >= lastPage"
                    class="flex items-center justify-center w-7 h-7 rounded-sm border border-paper-300
                           text-ink-500 hover:bg-paper-100 disabled:opacity-40 disabled:cursor-not-allowed
                           transition-colors duration-100"
                    @click="currentPage++"
                >
                    <ChevronRight class="w-3.5 h-3.5" />
                </button>
            </div>
        </div>

    </div>

    <!-- Confirm dialog -->
    <Dialog :open="confirmOpen" @update:open="confirmOpen = $event">
        <DialogContent class="max-w-sm p-0 overflow-hidden">
            <DialogHeader class="px-4 py-3 border-b border-paper-200 bg-paper-75">
                <DialogTitle class="font-serif text-lg">{{ __('Confirm') }}</DialogTitle>
            </DialogHeader>
            <div class="px-4 py-4">
                <DialogDescription class="text-sm text-ink-600">
                    {{ confirmMsg }}
                </DialogDescription>
            </div>
            <DialogFooter class="flex justify-end gap-2 px-4 py-3 border-t border-paper-200 bg-paper-75">
                <Button variant="outline" class="h-8 text-sm" @click="confirmOpen = false">
                    {{ __('Cancel') }}
                </Button>
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
