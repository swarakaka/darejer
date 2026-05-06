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
import {
  type Row,
  type Totals,
  deriveColumns,
  formatDisplay,
  humanize,
  isNumericColumn,
} from '@/lib/reportTable'

const { __ } = useTranslation()

const props = defineProps<{
  rows: Row[]
  totals?: Totals | null
}>()

const columns = computed<string[]>(() => deriveColumns(props.rows))

const numericColumnSet = computed<Set<string>>(() => {
  const set = new Set<string>()
  for (const c of columns.value) {
    if (isNumericColumn(props.rows, c)) {
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
    const v = totalsCellFor(c)
    if (v !== null && v !== undefined) {
      return true
    }
  }
  return false
})

const rowCount = computed(() => {
  const t = props.totals
  if (t && t.count !== undefined && t.count !== null) {
    return String(t.count)
  }
  return String(props.rows.length)
})
</script>

<template>
  <section
    class="relative overflow-hidden rounded-md border border-paper-200 bg-card shadow-[0_1px_0_rgba(0,0,0,0.02)] print:border-0 print:bg-transparent print:shadow-none"
  >
    <header
      class="flex items-center justify-between border-b border-paper-200 bg-linear-to-b from-paper-75 to-card px-5 py-3 print:hidden"
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

    <div
      v-if="rows.length === 0"
      class="flex flex-col items-center gap-2 px-5 py-12 text-ink-500 print:hidden"
    >
      <Inbox class="h-8 w-8 text-ink-300" />
      <p class="text-sm">{{ __('No data — adjust filters and click Apply.') }}</p>
    </div>

    <Table v-else class="text-[13px] print:text-[10.5px]">
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
            {{ formatDisplay(row[column], numericColumnSet.has(column)) }}
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
              {{ formatDisplay(totalsCellFor(column), numericColumnSet.has(column)) }}
            </template>
          </TableCell>
        </TableRow>
      </TableFooter>
    </Table>
  </section>
</template>
