<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import {
  ChevronUp,
  ChevronDown,
  ChevronsUpDown,
  ChevronLeft,
  ChevronRight,
  Search,
  Loader2,
  Inbox,
  Pencil,
  Eye,
  Trash2,
  MoreHorizontal,
} from 'lucide-vue-next'
import { ref, computed, watch, onMounted } from 'vue'
import { Button } from '@/components/ui/button'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from '@/components/ui/dialog'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'
import { useDataUrl } from '@/composables/useDataUrl'
import useTranslation from '@/composables/useTranslation'
import type { DarejerComponent } from '@/types/darejer'

const { __ } = useTranslation()

const props = defineProps<{
  component: DarejerComponent
  record: Record<string, unknown>
  errors: Record<string, string>
  formData?: Record<string, unknown>
}>()

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
}

interface GridRowAction {
  label: string
  icon?: string
  type: string
  urlPattern?: string
  dialog?: boolean
  confirm?: string
  variant: string
}

const rows = ref<Record<string, unknown>[]>([])
const total = ref(0)
const currentPage = ref(1)
const lastPage = ref(1)
const from = ref(0)
const to = ref(0)

const sortField = ref<string>((props.component.defaultSort as string) ?? '')
const sortOrder = ref<'asc' | 'desc'>((props.component.defaultOrder as 'asc' | 'desc') ?? 'asc')
const globalSearch = ref('')
const filters = ref<Record<string, string>>({})
const selected = ref<Set<unknown>>(new Set())

const confirmOpen = ref(false)
const confirmMsg = ref('')
const confirmAction = ref<GridRowAction | null>(null)
const confirmRow = ref<Record<string, unknown> | null>(null)

const columns = computed((): GridColumn[] =>
  ((props.component.gridColumns as GridColumn[]) ?? []).filter((c) => !c.hidden),
)

const rowActions = computed((): GridRowAction[] => (props.component.rowActions as GridRowAction[]) ?? [])

const perPage = computed(() => (props.component.perPage as number) ?? 15)
const isSelectable = computed(() => !!props.component.selectable)
const emptyMsg = computed(() => (props.component.emptyMessage as string) ?? __('No records found.'))

const allSelected = computed(() => rows.value.length > 0 && rows.value.every((r) => selected.value.has(r.id ?? r)))

const someSelected = computed(() => !allSelected.value && rows.value.some((r) => selected.value.has(r.id ?? r)))

const { load, http } = useDataUrl<Record<string, unknown>>(props.component.dataUrl as string | undefined, {
  perPage: perPage.value,
})

async function fetchData() {
  const result = await load({
    page: currentPage.value,
    perPage: perPage.value,
    sort: sortField.value || undefined,
    order: sortField.value ? sortOrder.value : undefined,
    search: globalSearch.value || undefined,
    filters: filters.value,
  })

  if (!result) return

  rows.value = result.data
  total.value = result.total
  currentPage.value = result.current_page
  lastPage.value = result.last_page
  from.value = result.from
  to.value = result.to
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
    selected.value = new Set(rows.value.map((r) => r.id ?? r))
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
  try {
    map = JSON.parse(column.badge)
  } catch {
    map = {}
  }
  const variant = map[String(value)] ?? 'neutral'
  const classes: Record<string, string> = {
    success: 'bg-success-50 text-success-700 border-success-100',
    warning: 'bg-warning-50 text-warning-700 border-warning-100',
    danger: 'bg-danger-50 text-danger-700 border-danger-100',
    info: 'bg-brand-50 text-brand-700 border-brand-100',
    neutral: 'bg-paper-100 text-ink-500 border-paper-200',
  }
  return classes[variant] ?? classes.neutral
}

// Translated case label sent from PHP via `Column::badge(EnumClass::class)`.
// Falls back to the raw value when no labels map was provided (plain array form).
function badgeLabel(column: GridColumn, value: unknown): string {
  if (value === null || value === undefined) return ''
  if (!column.badgeLabels) return String(value)
  let map: Record<string, string> = {}
  try {
    map = JSON.parse(column.badgeLabels)
  } catch {
    map = {}
  }
  return map[String(value)] ?? String(value)
}

const iconMap: Record<string, unknown> = {
  Pencil,
  Eye,
  Trash2,
  MoreHorizontal,
}

function resolveIcon(name?: string) {
  return name ? (iconMap[name] ?? null) : null
}

const pages = computed(() => {
  const totalPages = lastPage.value
  const cur = currentPage.value
  const delta = 2
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
  <div class="border-paper-200 bg-card col-span-full flex flex-col overflow-hidden rounded-md border">
    <!-- Toolbar -->
    <div class="border-paper-200 bg-paper-75 flex items-center gap-2 border-b px-3 py-2">
      <div v-if="component.searchable !== false" class="relative max-w-xs flex-1">
        <Search class="text-ink-400 absolute start-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2" />
        <input
          v-model="globalSearch"
          type="search"
          :placeholder="__('Search…')"
          class="border-paper-300 bg-card placeholder:text-ink-400 focus:border-brand-500 h-8 w-full rounded-sm border ps-8 pe-3 text-sm transition-colors duration-100 focus:outline-none"
        />
      </div>

      <div class="text-ink-400 ms-auto flex items-center gap-2 text-xs tabular-nums">
        <span v-if="total > 0">{{ __(':from–:to of :total', { from, to, total }) }}</span>
        <Loader2 v-if="http.processing" class="text-brand-500 h-3.5 w-3.5 animate-spin" />
      </div>
    </div>

    <!-- Table -->
    <div class="scrollbar-darejer overflow-x-auto">
      <table class="w-full border-collapse">
        <thead>
          <tr class="border-paper-200 bg-paper-75 border-b">
            <th v-if="isSelectable" class="h-9 w-9 px-3">
              <input
                type="checkbox"
                :checked="allSelected"
                :indeterminate.prop="someSelected"
                class="accent-brand-600 h-4 w-4 cursor-pointer rounded-sm"
                @change="toggleAll"
              />
            </th>

            <th
              v-for="col in columns"
              :key="col.field"
              class="h-9 px-3 text-start whitespace-nowrap select-none"
              :class="[
                col.sortable ? `hover:bg-paper-100 cursor-pointer transition-colors` : '',
                col.align === 'center' ? 'text-center' : '',
                col.align === 'right' ? 'text-end' : '',
              ]"
              :style="col.width ? { width: col.width } : {}"
              @click="toggleSort(col.field, col.sortable)"
            >
              <div
                class="flex items-center gap-1"
                :class="col.align === 'center' ? `justify-center` : col.align === 'right' ? `justify-end` : ''"
              >
                <span class="text-ink-400 text-[10px] font-semibold tracking-[0.08em] uppercase">
                  {{ col.label }}
                </span>
                <template v-if="col.sortable">
                  <ChevronUp v-if="sortField === col.field && sortOrder === 'asc'" class="text-brand-600 h-3 w-3" />
                  <ChevronDown
                    v-else-if="sortField === col.field && sortOrder === 'desc'"
                    class="text-brand-600 h-3 w-3"
                  />
                  <ChevronsUpDown v-else class="text-ink-300 h-3 w-3" />
                </template>
              </div>
            </th>

            <th v-if="rowActions.length" class="h-9 w-16 px-3" />
          </tr>
        </thead>

        <tbody>
          <!-- Loading skeleton -->
          <template v-if="http.processing && rows.length === 0">
            <tr v-for="i in perPage" :key="i" class="border-paper-100 border-b">
              <td v-if="isSelectable" class="h-9 px-3">
                <div class="bg-paper-100 h-4 w-4 animate-pulse rounded-sm" />
              </td>
              <td v-for="col in columns" :key="col.field" class="h-9 px-3">
                <div
                  class="bg-paper-100 h-3 animate-pulse rounded-sm"
                  :style="{ width: Math.random() * 40 + 40 + '%' }"
                />
              </td>
              <td v-if="rowActions.length" class="h-9 px-3" />
            </tr>
          </template>

          <!-- Empty state -->
          <template v-else-if="!http.processing && rows.length === 0">
            <tr>
              <td
                :colspan="columns.length + (isSelectable ? 1 : 0) + (rowActions.length ? 1 : 0)"
                class="px-3 py-10 text-center"
              >
                <div class="text-ink-400 flex flex-col items-center gap-2">
                  <Inbox class="h-8 w-8" />
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
              class="border-paper-100 hover:bg-paper-50 border-b transition-colors duration-75"
              :class="selected.has(row.id ?? row) ? 'bg-brand-50' : ''"
            >
              <td v-if="isSelectable" class="h-9 px-3">
                <input
                  type="checkbox"
                  :checked="selected.has(row.id ?? row)"
                  class="accent-brand-600 h-4 w-4 cursor-pointer rounded-sm"
                  @change="toggleRow(row.id ?? row)"
                />
              </td>

              <td
                v-for="col in columns"
                :key="col.field"
                class="text-ink-800 h-9 px-3 text-sm"
                :class="[
                  col.align === 'center' ? 'text-center' : '',
                  col.align === 'right' ? `text-end tabular-nums` : '',
                ]"
              >
                <span
                  v-if="col.badge"
                  class="inline-flex items-center rounded-sm border px-1.5 py-0.5 text-[10px] font-semibold tracking-wide uppercase"
                  :class="badgeClass(col, row[col.field])"
                >
                  {{ badgeLabel(col, row[col.field]) }}
                </span>

                <span v-else class="block max-w-xs truncate">
                  {{ row[col.field] ?? '—' }}
                </span>
              </td>

              <td v-if="rowActions.length" class="h-9 px-2">
                <div class="flex items-center justify-end gap-0.5">
                  <!-- Single action: show inline -->
                  <template v-if="rowActions.length === 1">
                    <button
                      type="button"
                      class="flex h-7 w-7 items-center justify-center rounded-sm transition-colors duration-100"
                      :class="
                        rowActions[0].variant === 'destructive'
                          ? `text-danger-600 hover:bg-danger-50 hover:text-danger-700`
                          : `text-ink-400 hover:bg-paper-100 hover:text-ink-700`
                      "
                      :title="rowActions[0].label"
                      @click="handleRowAction(rowActions[0], row)"
                    >
                      <component :is="resolveIcon(rowActions[0].icon)" v-if="rowActions[0].icon" class="h-3.5 w-3.5" />
                      <span v-else class="text-xs">{{ rowActions[0].label }}</span>
                    </button>
                  </template>

                  <!-- Multiple actions: dropdown -->
                  <template v-else>
                    <DropdownMenu>
                      <DropdownMenuTrigger as-child>
                        <button
                          type="button"
                          class="text-ink-400 hover:bg-paper-100 hover:text-ink-700 flex h-7 w-7 items-center justify-center rounded-sm transition-colors duration-100"
                        >
                          <MoreHorizontal class="h-4 w-4" />
                        </button>
                      </DropdownMenuTrigger>
                      <DropdownMenuContent align="end" class="w-36">
                        <DropdownMenuItem
                          v-for="action in rowActions"
                          :key="action.label"
                          class="flex cursor-pointer items-center gap-2 text-sm"
                          :class="action.variant === 'destructive' ? `text-danger-700 focus:text-danger-700` : ''"
                          @click="handleRowAction(action, row)"
                        >
                          <component :is="resolveIcon(action.icon)" v-if="action.icon" class="h-3.5 w-3.5 shrink-0" />
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
      class="border-paper-200 bg-paper-75 flex items-center justify-between border-t px-3 py-2"
    >
      <span class="text-ink-400 text-xs tabular-nums">
        {{ __('Showing :from–:to of :total records', { from, to, total }) }}
      </span>

      <div class="flex items-center gap-1">
        <button
          type="button"
          :disabled="currentPage <= 1"
          class="border-paper-300 text-ink-500 hover:bg-paper-100 flex h-7 w-7 items-center justify-center rounded-sm border transition-colors duration-100 disabled:cursor-not-allowed disabled:opacity-40"
          @click="currentPage--"
        >
          <ChevronLeft class="h-3.5 w-3.5" />
        </button>

        <template v-for="(p, idx) in pages" :key="`${idx}-${p}`">
          <span v-if="p === '...'" class="text-ink-300 flex h-7 w-7 items-center justify-center text-xs"> … </span>
          <button
            v-else
            type="button"
            class="flex h-7 w-7 items-center justify-center rounded-sm border text-xs tabular-nums transition-colors duration-100"
            :class="
              currentPage === p
                ? 'border-brand-600 bg-brand-600 text-white'
                : `border-paper-300 text-ink-600 hover:bg-paper-100`
            "
            @click="currentPage = p as number"
          >
            {{ p }}
          </button>
        </template>

        <button
          type="button"
          :disabled="currentPage >= lastPage"
          class="border-paper-300 text-ink-500 hover:bg-paper-100 flex h-7 w-7 items-center justify-center rounded-sm border transition-colors duration-100 disabled:cursor-not-allowed disabled:opacity-40"
          @click="currentPage++"
        >
          <ChevronRight class="h-3.5 w-3.5" />
        </button>
      </div>
    </div>
  </div>

  <!-- Confirm dialog -->
  <Dialog :open="confirmOpen" @update:open="confirmOpen = $event">
    <DialogContent class="max-w-sm overflow-hidden p-0">
      <DialogHeader class="border-paper-200 bg-paper-75 border-b px-4 py-3">
        <DialogTitle class="text-lg">{{ __('Confirm') }}</DialogTitle>
      </DialogHeader>
      <div class="px-4 py-4">
        <DialogDescription class="text-ink-600 text-sm">
          {{ confirmMsg }}
        </DialogDescription>
      </div>
      <DialogFooter class="border-paper-200 bg-paper-75 flex justify-end gap-2 border-t px-4 py-3">
        <Button variant="outline" class="h-8 text-sm" @click="confirmOpen = false">
          {{ __('Cancel') }}
        </Button>
        <Button
          class="bg-danger-600 hover:bg-danger-700 h-8 border-transparent text-sm text-white"
          @click="executeConfirmed"
        >
          {{ confirmAction?.label }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
