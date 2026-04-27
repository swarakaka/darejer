<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { router }                   from '@inertiajs/vue3'
import { useDataUrl }               from '@/composables/useDataUrl'
import {
    ChevronRight, ChevronDown,
    Loader2, Inbox,
    Pencil, Eye, Trash2, MoreHorizontal,
} from 'lucide-vue-next'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {
    Dialog, DialogContent, DialogHeader,
    DialogTitle, DialogDescription, DialogFooter,
} from '@/components/ui/dialog'
import { Button }                from '@/components/ui/button'
import FieldWrapper              from '@/components/darejer/FieldWrapper.vue'
import type { DarejerComponent } from '@/types/darejer'

const props = defineProps<{
    component: DarejerComponent
    record:    Record<string, unknown>
    errors:    Record<string, string>
    formData?: Record<string, unknown>
}>()

interface TreeCol {
    field:     string
    label:     string
    sortable?: boolean
    width?:    string
    align?:    string
    isTree?:   boolean
}

interface RowAct {
    label:       string
    icon?:       string
    type:        string
    urlPattern?: string
    dialog?:     boolean
    confirm?:    string
    variant:     string
}

type TreeRow = Record<string, unknown> & { children?: TreeRow[] }

const rows     = ref<TreeRow[]>([])
const expanded = ref<Set<unknown>>(new Set())

const { load, http } = useDataUrl<TreeRow>(
    props.component.dataUrl as string | undefined,
    {
        tree:        true,
        parentField: (props.component.parentField as string) ?? 'parent_id',
    },
)

const confirmOpen   = ref(false)
const confirmMsg    = ref('')
const confirmAction = ref<RowAct | null>(null)
const confirmRow    = ref<TreeRow | null>(null)

const columns    = computed((): TreeCol[] => (props.component.treeColumns as TreeCol[]) ?? [])
const rowActions = computed((): RowAct[]  => (props.component.rowActions  as RowAct[])  ?? [])
const expandAll  = computed(() => !!props.component.expandAll)
const emptyMsg   = computed(() => (props.component.emptyMessage as string) ?? 'No records found.')
const keyField   = computed(() => (props.component.keyField as string) ?? 'id')

async function fetchData() {
    const result = await load()
    if (!result) return

    rows.value = result.data as TreeRow[]

    if (expandAll.value) {
        const expandAllRows = (items: TreeRow[]) => {
            for (const item of items) {
                if (item.children?.length) {
                    expanded.value.add(item[keyField.value])
                    expandAllRows(item.children)
                }
            }
        }
        expandAllRows(rows.value)
        expanded.value = new Set(expanded.value)
    }
}

onMounted(() => fetchData())

function toggleExpand(id: unknown) {
    if (expanded.value.has(id)) {
        expanded.value.delete(id)
    } else {
        expanded.value.add(id)
    }
    expanded.value = new Set(expanded.value)
}

interface FlatRow {
    row:         TreeRow
    depth:       number
    hasChildren: boolean
}

function flatten(items: TreeRow[], depth = 0): FlatRow[] {
    const result: FlatRow[] = []
    for (const item of items) {
        const id          = item[keyField.value]
        const hasChildren = !!(item.children?.length)
        result.push({ row: item, depth, hasChildren })
        if (hasChildren && expanded.value.has(id)) {
            result.push(...flatten(item.children!, depth + 1))
        }
    }
    return result
}

const flatRows = computed(() => flatten(rows.value))

function resolveUrl(pattern: string, row: TreeRow): string {
    return pattern.replace(/\{(\w+)\}/g, (_, key) => String(row[key] ?? ''))
}

function handleAction(action: RowAct, row: TreeRow) {
    if (action.confirm) {
        confirmMsg.value    = action.confirm
        confirmAction.value = action
        confirmRow.value    = row
        confirmOpen.value   = true
        return
    }
    execute(action, row)
}

function execute(action: RowAct, row: TreeRow) {
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
    if (confirmAction.value && confirmRow.value) execute(confirmAction.value, confirmRow.value)
    confirmOpen.value = false
}

const iconMap: Record<string, unknown> = { Pencil, Eye, Trash2, MoreHorizontal }
const resolveIcon = (name?: string) => name ? (iconMap[name] ?? null) : null

const treeCol = computed(() => columns.value.find(c => c.isTree) ?? columns.value[0])
</script>

<template>
    <FieldWrapper
        :component="component"
        :record="record"
        :errors="errors"
        :form-data="formData"
        class="col-span-full"
    >
        <template #default>
            <div class="border border-paper-200 rounded-md overflow-hidden bg-white">

                <!-- Loading -->
                <div
                    v-if="http.processing"
                    class="flex items-center justify-center py-10 text-ink-400"
                >
                    <Loader2 class="w-5 h-5 animate-spin" />
                </div>

                <!-- Empty -->
                <div
                    v-else-if="flatRows.length === 0"
                    class="flex flex-col items-center gap-2 py-10 text-ink-400"
                >
                    <Inbox class="w-8 h-8" />
                    <span class="text-sm">{{ emptyMsg }}</span>
                </div>

                <!-- Table -->
                <table v-else class="w-full border-collapse">
                    <thead>
                        <tr class="bg-paper-75 border-b border-paper-200">
                            <th
                                v-for="col in columns"
                                :key="col.field"
                                class="px-3 h-9 text-left whitespace-nowrap"
                                :class="col.align === 'right' ? 'text-right' : col.align === 'center' ? 'text-center' : ''"
                                :style="col.width ? { width: col.width } : {}"
                            >
                                <span class="text-[10px] font-semibold uppercase tracking-[0.08em] text-ink-400">
                                    {{ col.label }}
                                </span>
                            </th>
                            <th v-if="rowActions.length" class="w-16 px-3 h-9" />
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="{ row, depth, hasChildren } in flatRows"
                            :key="String(row[keyField])"
                            class="border-b border-paper-100 hover:bg-paper-50 transition-colors duration-75 last:border-b-0"
                        >
                            <td
                                v-for="col in columns"
                                :key="col.field"
                                class="px-3 h-9 text-sm text-ink-800"
                                :class="col.align === 'right' ? 'text-right tabular-nums' : col.align === 'center' ? 'text-center' : ''"
                            >
                                <div
                                    v-if="col.field === treeCol?.field"
                                    class="flex items-center gap-1"
                                    :style="{ paddingLeft: `${depth * 1.25}rem` }"
                                >
                                    <button
                                        v-if="hasChildren"
                                        type="button"
                                        class="flex items-center justify-center w-4 h-4
                                               text-ink-400 hover:text-ink-700 transition-colors shrink-0"
                                        @click="toggleExpand(row[keyField])"
                                    >
                                        <ChevronDown v-if="expanded.has(row[keyField])" class="w-3.5 h-3.5" />
                                        <ChevronRight v-else class="w-3.5 h-3.5" />
                                    </button>
                                    <span v-else class="w-4 shrink-0" />
                                    <span class="truncate">{{ row[col.field] ?? '—' }}</span>
                                </div>

                                <span v-else class="block truncate max-w-xs">
                                    {{ row[col.field] ?? '—' }}
                                </span>
                            </td>

                            <td v-if="rowActions.length" class="px-2 h-9">
                                <div class="flex items-center justify-end">
                                    <template v-if="rowActions.length === 1">
                                        <button
                                            type="button"
                                            class="flex items-center justify-center w-7 h-7 rounded-sm
                                                   text-ink-400 hover:text-ink-700 hover:bg-paper-100 transition-colors"
                                            :title="rowActions[0].label"
                                            @click="handleAction(rowActions[0], row)"
                                        >
                                            <component :is="resolveIcon(rowActions[0].icon)" v-if="rowActions[0].icon" class="w-3.5 h-3.5" />
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
                                                    @click="handleAction(action, row)"
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
        </template>
    </FieldWrapper>

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
