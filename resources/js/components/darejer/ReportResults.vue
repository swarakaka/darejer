<script setup lang="ts">
import { computed } from 'vue'
import { Inbox } from 'lucide-vue-next'
import {
  Table,
  TableBody,
  TableCell,
  TableFooter,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import useTranslation from '@/composables/useTranslation'

const { __ } = useTranslation()

type Row = Record<string, unknown>
type Totals = Record<string, unknown>

const props = defineProps<{
  rows: Row[]
  totals?: Totals | null
}>()

// Keys we never show as columns — internal IDs, foreign keys, leading-underscore
// metadata. The server picks what to expose; we just sanitize the auto-derived
// header order so primary keys / FK columns don't pollute the table.
const HIDDEN_KEY_PATTERNS = [/^id$/, /_id$/, /^_/]

function isHidden(key: string): boolean {
  return HIDDEN_KEY_PATTERNS.some((re) => re.test(key))
}

const columns = computed<string[]>(() => {
  const first = props.rows[0]
  if (!first) {
    return []
  }
  return Object.keys(first).filter((k) => !isHidden(k))
})

function humanize(key: string): string {
  return key
    .replace(/_/g, ' ')
    .replace(/\b\w/g, (c) => c.toUpperCase())
}

const NUMERIC_RE = /^-?\d+(\.\d+)?$/

function isNumeric(value: unknown): boolean {
  if (typeof value === 'number') {
    return true
  }
  return typeof value === 'string' && NUMERIC_RE.test(value)
}

function isNumericColumn(key: string): boolean {
  for (const row of props.rows) {
    const v = row[key]
    if (v === null || v === undefined || v === '') {
      continue
    }
    return isNumeric(v)
  }
  return false
}

function formatNumber(value: unknown): string {
  if (typeof value === 'number') {
    return value.toLocaleString(undefined, {
      minimumFractionDigits: 2,
      maximumFractionDigits: 6,
    })
  }
  if (typeof value === 'string' && NUMERIC_RE.test(value)) {
    const n = Number(value)
    if (Number.isFinite(n)) {
      return n.toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 6,
      })
    }
  }
  return String(value ?? '')
}

function display(value: unknown, key: string): string {
  if (value === null || value === undefined || value === '') {
    return '—'
  }
  if (isNumericColumn(key) && isNumeric(value)) {
    return formatNumber(value)
  }
  return String(value)
}

const numericColumnSet = computed<Set<string>>(() => {
  const set = new Set<string>()
  for (const c of columns.value) {
    if (isNumericColumn(c)) {
      set.add(c)
    }
  }
  return set
})

function totalsCellFor(column: string): unknown {
  const t = props.totals
  if (!t) {
    return null
  }
  return Object.prototype.hasOwnProperty.call(t, column) ? t[column] : null
}

const hasAnyTotal = computed(() => {
  if (!props.totals) {
    return false
  }
  for (const c of columns.value) {
    if (totalsCellFor(c) !== null && totalsCellFor(c) !== undefined) {
      return true
    }
  }
  return false
})

const rowCount = computed(() => {
  const t = props.totals as Record<string, unknown> | null | undefined
  if (t && (t.count !== undefined && t.count !== null)) {
    return String(t.count)
  }
  return String(props.rows.length)
})
</script>

<template>
  <section
    class="relative overflow-hidden rounded-md border border-paper-200 bg-card shadow-[0_1px_0_rgba(0,0,0,0.02)]"
  >
    <header
      class="flex items-center justify-between border-b border-paper-200 bg-linear-to-b from-paper-75 to-card px-5 py-3"
    >
      <h2 class="text-[14px] leading-tight font-semibold tracking-tight text-ink-900">
        {{ __('Results') }}
      </h2>
      <span
        class="inline-flex items-center gap-1.5 rounded-full bg-paper-100 px-2 py-0.5 text-2xs font-bold tracking-[0.14em] text-ink-600 uppercase ring-1 ring-paper-200 ring-inset"
      >
        {{ rowCount }} {{ __('rows') }}
      </span>
    </header>

    <div v-if="rows.length === 0" class="flex flex-col items-center gap-2 px-5 py-12 text-ink-500">
      <Inbox class="h-8 w-8 text-ink-300" />
      <p class="text-sm">{{ __('No data — adjust filters and click Apply.') }}</p>
    </div>

    <Table v-else class="text-[13px]">
      <TableHeader>
        <TableRow>
          <TableHead
            v-for="column in columns"
            :key="column"
            :class="numericColumnSet.has(column) ? 'text-end' : ''"
          >
            {{ __(humanize(column)) }}
          </TableHead>
        </TableRow>
      </TableHeader>
      <TableBody>
        <TableRow v-for="(row, idx) in rows" :key="idx">
          <TableCell
            v-for="column in columns"
            :key="column"
            :class="[
              numericColumnSet.has(column) ? 'text-end font-mono tabular-nums' : '',
            ]"
          >
            {{ display(row[column], column) }}
          </TableCell>
        </TableRow>
      </TableBody>
      <TableFooter v-if="hasAnyTotal">
        <TableRow>
          <TableCell
            v-for="(column, i) in columns"
            :key="column"
            :class="[
              'font-semibold',
              numericColumnSet.has(column) ? 'text-end font-mono tabular-nums' : '',
            ]"
          >
            <template v-if="i === 0 && totalsCellFor(column) === null">
              {{ __('Total') }}
            </template>
            <template v-else-if="totalsCellFor(column) !== null">
              {{ display(totalsCellFor(column), column) }}
            </template>
          </TableCell>
        </TableRow>
      </TableFooter>
    </Table>
  </section>
</template>
