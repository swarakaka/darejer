<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { useDataUrl } from '@/composables/useDataUrl'
import {
  ChevronRight,
  ChevronDown,
  Loader2,
  Inbox,
  Pencil,
  Eye,
  Trash2,
  MoreHorizontal,
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
import FieldWrapper from '@/components/darejer/FieldWrapper.vue'
import useTranslation from '@/composables/useTranslation'
import type { DarejerComponent } from '@/types/darejer'

const { __ } = useTranslation()

const props = defineProps<{
  component: DarejerComponent
  record: Record<string, unknown>
  errors: Record<string, string>
  formData?: Record<string, unknown>
}>()

interface TreeCol {
  field: string
  label: string
  sortable?: boolean
  width?: string
  align?: string
  isTree?: boolean
  badge?: string
  badgeLabels?: string
}

interface RowAct {
  label: string
  icon?: string
  type: string
  urlPattern?: string
  dialog?: boolean
  confirm?: string
  variant: string
}

type TreeRow = Record<string, unknown> & { children?: TreeRow[] }

const rows = ref<TreeRow[]>([])
const expanded = ref<Set<unknown>>(new Set())

const { load, http } = useDataUrl<TreeRow>(props.component.dataUrl as string | undefined, {
  tree: true,
  parentField: (props.component.parentField as string) ?? 'parent_id',
})

const confirmOpen = ref(false)
const confirmMsg = ref('')
const confirmAction = ref<RowAct | null>(null)
const confirmRow = ref<TreeRow | null>(null)

const columns = computed((): TreeCol[] => (props.component.treeColumns as TreeCol[]) ?? [])
const rowActions = computed((): RowAct[] => (props.component.rowActions as RowAct[]) ?? [])
const expandAll = computed(() => !!props.component.expandAll)
const emptyMsg = computed(() => (props.component.emptyMessage as string) ?? __('No records found.'))
const keyField = computed(() => (props.component.keyField as string) ?? 'id')

// Tree depth → logical inline-start padding class (RTL-safe).
// Extends the original `${depth * 1.25}rem` rule using only static Tailwind
// spacing utilities so the JIT can pre-generate the classes at build time.
const indentMap = [
  '',
  'ps-5',
  'ps-10',
  'ps-14',
  'ps-20',
  'ps-24',
  'ps-28',
  'ps-32',
  'ps-40',
  'ps-48',
  'ps-56',
]
function indentClass(depth: number): string {
  return indentMap[Math.min(depth, indentMap.length - 1)] ?? 'ps-56'
}

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
  row: TreeRow
  depth: number
  hasChildren: boolean
}

function flatten(items: TreeRow[], depth = 0): FlatRow[] {
  const result: FlatRow[] = []
  for (const item of items) {
    const id = item[keyField.value]
    const hasChildren = !!item.children?.length
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
    confirmMsg.value = action.confirm
    confirmAction.value = action
    confirmRow.value = row
    confirmOpen.value = true
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
const resolveIcon = (name?: string) => (name ? (iconMap[name] ?? null) : null)

const treeCol = computed(() => columns.value.find((c) => c.isTree) ?? columns.value[0])

function badgeKey(value: unknown): string {
  if (value === true) return '1'
  if (value === false) return '0'
  return value == null ? '' : String(value)
}

function badgeClass(col: TreeCol, value: unknown): string {
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
    destructive: 'bg-danger-50 text-danger-700 ring-danger-100',
    info: 'bg-brand-50 text-brand-700 ring-brand-100',
    muted: 'bg-paper-100 text-ink-500 ring-paper-200',
    neutral: 'bg-paper-100 text-ink-600 ring-paper-200',
  }
  return classes[variant] ?? classes.neutral
}

function badgeLabel(col: TreeCol, value: unknown): string {
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
</script>

<template>
  <FieldWrapper
    :component="component"
    :record="record"
    :errors="errors"
    :form-data="formData"
    class="px-6 py-6"
  >
    <template #default>
      <div class="overflow-x-auto rounded-md border border-paper-200 bg-card">
        <!-- Loading -->
        <div v-if="http.processing" class="flex items-center justify-center py-10 text-ink-400">
          <Loader2 class="h-5 w-5 animate-spin" />
        </div>

        <!-- Empty -->
        <div
          v-else-if="flatRows.length === 0"
          class="flex flex-col items-center gap-2 py-10 text-ink-400"
        >
          <Inbox class="h-8 w-8" />
          <span class="text-sm">{{ emptyMsg }}</span>
        </div>

        <!-- Table -->
        <table v-else class="w-full border-collapse">
          <thead>
            <tr class="sticky top-0 z-10 border-b border-paper-200 bg-paper-75">
              <th
                v-for="col in columns"
                :key="col.field"
                class="h-9 px-3 text-start whitespace-nowrap"
                :class="
                  col.align === 'right' ? 'text-end' : col.align === 'center' ? `text-center` : ''
                "
                :style="col.width ? { width: col.width } : {}"
              >
                <span class="text-[10px] font-semibold tracking-[0.08em] text-ink-400 uppercase">
                  {{ col.label }}
                </span>
              </th>
              <th v-if="rowActions.length" class="h-9 w-16 px-3" />
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="{ row, depth, hasChildren } in flatRows"
              :key="String(row[keyField])"
              class="border-b border-paper-100 transition-colors duration-75 last:border-b-0 hover:bg-paper-50"
            >
              <td
                v-for="col in columns"
                :key="col.field"
                class="h-9 px-3 text-sm text-ink-800"
                :class="
                  col.align === 'right'
                    ? `text-end tabular-nums`
                    : col.align === 'center'
                      ? `text-center`
                      : ''
                "
              >
                <div
                  v-if="col.field === treeCol?.field"
                  class="flex items-center gap-1"
                  :class="indentClass(depth)"
                >
                  <button
                    v-if="hasChildren"
                    type="button"
                    class="flex h-4 w-4 shrink-0 items-center justify-center text-ink-400 transition-colors hover:text-ink-700"
                    @click="toggleExpand(row[keyField])"
                  >
                    <ChevronDown v-if="expanded.has(row[keyField])" class="h-3.5 w-3.5" />
                    <ChevronRight v-else class="h-3.5 w-3.5 rtl:rotate-180" />
                  </button>
                  <span v-else class="w-4 shrink-0" />
                  <span class="truncate">{{ row[col.field] ?? '—' }}</span>
                </div>

                <span
                  v-else-if="col.badge"
                  class="inline-flex items-center rounded-sm px-1.5 py-0.5 text-[10px] font-bold tracking-[0.04em] uppercase ring-1 ring-inset"
                  :class="badgeClass(col, row[col.field])"
                >
                  {{ badgeLabel(col, row[col.field]) }}
                </span>

                <span v-else class="block max-w-xs truncate">
                  {{ row[col.field] ?? '—' }}
                </span>
              </td>

              <td v-if="rowActions.length" class="h-9 px-2">
                <div class="flex items-center justify-end">
                  <template v-if="rowActions.length === 1">
                    <button
                      type="button"
                      class="flex h-7 w-7 items-center justify-center rounded-sm text-ink-400 transition-colors hover:bg-paper-100 hover:text-ink-700"
                      :title="rowActions[0].label"
                      @click="handleAction(rowActions[0], row)"
                    >
                      <component
                        :is="resolveIcon(rowActions[0].icon)"
                        v-if="rowActions[0].icon"
                        class="h-3.5 w-3.5"
                      />
                    </button>
                  </template>
                  <template v-else>
                    <DropdownMenu>
                      <DropdownMenuTrigger as-child>
                        <button
                          type="button"
                          class="flex h-7 w-7 items-center justify-center rounded-sm text-ink-400 transition-colors hover:bg-paper-100 hover:text-ink-700"
                        >
                          <MoreHorizontal class="h-4 w-4" />
                        </button>
                      </DropdownMenuTrigger>
                      <DropdownMenuContent align="end" class="w-36">
                        <DropdownMenuItem
                          v-for="action in rowActions"
                          :key="action.label"
                          class="flex cursor-pointer items-center gap-2 text-sm"
                          :class="
                            action.variant === 'destructive'
                              ? `text-danger-700 focus:text-danger-700`
                              : ''
                          "
                          @click="handleAction(action, row)"
                        >
                          <component
                            :is="resolveIcon(action.icon)"
                            v-if="action.icon"
                            class="h-3.5 w-3.5 shrink-0"
                          />
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
    <DialogContent class="max-w-sm overflow-hidden p-0">
      <DialogHeader class="border-b border-paper-200 bg-paper-75 px-4 py-3">
        <DialogTitle class="text-lg">{{ __('Confirm') }}</DialogTitle>
      </DialogHeader>
      <div class="px-4 py-4">
        <DialogDescription class="text-sm text-ink-600">{{ confirmMsg }}</DialogDescription>
      </div>
      <DialogFooter class="flex justify-end gap-2 border-t border-paper-200 bg-paper-75 px-4 py-3">
        <Button variant="outline" class="h-8 text-sm" @click="confirmOpen = false">{{
          __('Cancel')
        }}</Button>
        <Button
          class="h-8 border-transparent bg-danger-600 text-sm text-white hover:bg-danger-700"
          @click="executeConfirmed"
        >
          {{ confirmAction?.label }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
