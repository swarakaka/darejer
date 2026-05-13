<script setup lang="ts">
import { computed } from 'vue'
import { Badge } from '@/components/ui/badge'
import FieldWrapper from '@/components/darejer/FieldWrapper.vue'
import useTranslation from '@/composables/useTranslation'
import type { DarejerComponent } from '@/types/darejer'

type ColumnType = 'text' | 'number' | 'money' | 'date' | 'datetime' | 'badge' | 'boolean'

interface TableCol {
  field: string
  label: string
  type: ColumnType
  width?: string
  decimals?: number
  dateFormat?: string
  currencyField?: string
  decimalsField?: string
  badgeMap?: Record<string, string> | null
  badgeLabels?: Record<string, string> | null
  booleanTrueLabel?: string
  booleanFalseLabel?: string
  alignRight?: boolean
  emptyText?: string
  translatable?: boolean
  footer?: string
}

const props = defineProps<{
  component: DarejerComponent
  record: Record<string, unknown>
  errors: Record<string, string>
  formData?: Record<string, unknown>
}>()

const { __, resolveTranslatable } = useTranslation()

const columns = computed((): TableCol[] => (props.component.tableColumns as TableCol[]) ?? [])

const source = computed(() => props.formData ?? props.record)

const rows = computed<Record<string, unknown>[]>(() => {
  const v = source.value[props.component.name]
  return Array.isArray(v) ? (v as Record<string, unknown>[]) : []
})

const emptyMessage = computed<string>(
  () => (props.component.emptyMessage as string | undefined) ?? __('No items to display.'),
)

function resolvePath(obj: unknown, path: string): unknown {
  return path
    .split('.')
    .reduce<unknown>(
      (acc, key) =>
        acc && typeof acc === 'object' ? (acc as Record<string, unknown>)[key] : undefined,
      obj,
    )
}

const pad2 = (n: number) => String(n).padStart(2, '0')

function formatPhpDate(date: Date, format: string): string {
  const hours24 = date.getHours()
  const hours12 = hours24 % 12 || 12
  const tokens: Record<string, string> = {
    Y: String(date.getFullYear()),
    y: String(date.getFullYear()).slice(-2),
    m: pad2(date.getMonth() + 1),
    n: String(date.getMonth() + 1),
    d: pad2(date.getDate()),
    j: String(date.getDate()),
    H: pad2(hours24),
    G: String(hours24),
    h: pad2(hours12),
    g: String(hours12),
    i: pad2(date.getMinutes()),
    s: pad2(date.getSeconds()),
    a: hours24 < 12 ? 'am' : 'pm',
    A: hours24 < 12 ? 'AM' : 'PM',
  }
  return format.replace(/Y|y|m|n|d|j|H|G|h|g|i|s|a|A/g, (t) => tokens[t] ?? t)
}

function decimalsFor(col: TableCol, row: Record<string, unknown>): number {
  if (col.decimalsField) {
    const v = resolvePath(row, col.decimalsField) ?? resolvePath(source.value, col.decimalsField)
    if (typeof v === 'number') return v
    if (typeof v === 'string' && v !== '') {
      const n = Number(v)
      if (Number.isFinite(n)) return n
    }
  }
  return col.decimals ?? 0
}

function currencyFor(col: TableCol, row: Record<string, unknown>): string | null {
  if (!col.currencyField) return null
  const v = resolvePath(row, col.currencyField) ?? resolvePath(source.value, col.currencyField)
  return v ? String(v) : null
}

interface Cell {
  kind: 'text' | 'badge' | 'empty'
  text: string
  badgeKey?: string
  alignRight?: boolean
}

const badgeToneMap: Record<string, string> = {
  success: 'bg-success-50 text-success-700 border-success-100',
  warning: 'bg-warning-50 text-warning-700 border-warning-100',
  danger: 'bg-danger-50 text-danger-700 border-danger-100',
  info: 'bg-brand-50 text-brand-700 border-brand-100',
  neutral: 'bg-paper-100 text-ink-500 border-paper-200',
}

// Translatable JSON arrives as an object (`{en: 'X', ar: 'س'}`) when
// HasTranslations attributes pass through Inertia, or as a JSON-encoded
// string when the cast is bypassed. Detect both so dot-path lookups onto
// translatable columns (`item.name`, `uom.name`, …) render the resolved
// string instead of `[object Object]` — without requiring every column
// to opt in via `->translatable()`.
function looksTranslatable(v: unknown): boolean {
  if (v && typeof v === 'object' && !Array.isArray(v)) return true
  if (typeof v === 'string') {
    const t = v.trim()
    return t.startsWith('{') && t.endsWith('}')
  }
  return false
}

function maybeResolveTranslatable(v: unknown, col: TableCol): unknown {
  if (col.translatable || looksTranslatable(v)) return resolveTranslatable(v)
  return v
}

function renderCell(col: TableCol, row: Record<string, unknown>): Cell {
  const raw = resolvePath(row, col.field)
  const value = maybeResolveTranslatable(raw, col)
  const alignRight = !!col.alignRight
  if (value === null || value === undefined || value === '') {
    if (col.type === 'boolean') {
      return {
        kind: 'text',
        text: col.booleanFalseLabel ?? __('No'),
        alignRight,
      }
    }
    return { kind: 'empty', text: col.emptyText ?? '—', alignRight }
  }

  if (col.type === 'badge') {
    const map = col.badgeMap ?? null
    const key = map ? (map[String(value)] ?? 'neutral') : 'neutral'
    const labels = col.badgeLabels ?? null
    const text = labels?.[String(value)] ?? String(value)
    return { kind: 'badge', text, badgeKey: key, alignRight }
  }

  if (col.type === 'boolean') {
    const truthy =
      value === true ||
      value === 1 ||
      (typeof value === 'string' &&
        ['1', 'true', 'yes', 'on'].includes(value.toLowerCase()))
    return {
      kind: 'text',
      text: truthy
        ? (col.booleanTrueLabel ?? __('Yes'))
        : (col.booleanFalseLabel ?? __('No')),
      alignRight,
    }
  }

  if (col.type === 'date' || col.type === 'datetime') {
    const d = new Date(String(value))
    if (Number.isNaN(d.getTime())) return { kind: 'text', text: String(value), alignRight }
    if (col.dateFormat) return { kind: 'text', text: formatPhpDate(d, col.dateFormat), alignRight }
    const opts: Intl.DateTimeFormatOptions =
      col.type === 'datetime'
        ? {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
          }
        : { year: 'numeric', month: '2-digit', day: '2-digit' }
    return { kind: 'text', text: new Intl.DateTimeFormat(undefined, opts).format(d), alignRight }
  }

  if (col.type === 'number' || col.type === 'money') {
    const n = Number(value)
    if (!Number.isFinite(n)) return { kind: 'text', text: String(value), alignRight: true }
    const dec = decimalsFor(col, row)
    const formatted = new Intl.NumberFormat(undefined, {
      minimumFractionDigits: dec,
      maximumFractionDigits: dec,
    }).format(n)
    if (col.type === 'money') {
      const code = currencyFor(col, row)
      return {
        kind: 'text',
        text: code ? `${formatted} ${code}` : formatted,
        alignRight: true,
      }
    }
    return { kind: 'text', text: formatted, alignRight: true }
  }

  return { kind: 'text', text: String(value), alignRight }
}

const gridTemplate = computed(() =>
  columns.value.map((c) => c.width ?? 'minmax(8rem, 1fr)').join(' '),
)

const AGGREGATORS = ['sum', 'avg', 'min', 'max', 'count'] as const
type Aggregator = (typeof AGGREGATORS)[number]
const AGG_RE = /\b(sum|avg|min|max|count)\s*\(\s*([^()]*?)\s*\)/g

function numericRowValue(row: Record<string, unknown>, field: string): number {
  const v = resolvePath(row, field)
  const n = typeof v === 'number' ? v : Number(v)
  return Number.isFinite(n) ? n : 0
}

function compileRowExpression(expr: string, fieldSet: Set<string>): ((row: Record<string, unknown>) => number) | null {
  const trimmed = expr.trim()
  if (trimmed === '') return () => 1
  const tokens = trimmed.match(/\b[a-zA-Z_][a-zA-Z0-9_]*\b/g) ?? []
  const deps = Array.from(new Set(tokens.filter((t) => fieldSet.has(t))))
  try {
    const fn = new Function(...deps, `"use strict"; return (${trimmed});`) as (
      ...args: number[]
    ) => unknown
    return (row) => {
      const args = deps.map((f) => numericRowValue(row, f))
      const result = fn(...args)
      const n = typeof result === 'number' ? result : Number(result)
      return Number.isFinite(n) ? n : 0
    }
  } catch (e) {
    console.error(`[Table] failed to compile footer expression: ${expr}`, e)
    return null
  }
}

function aggregate(kind: Aggregator, values: number[]): number {
  if (kind === 'count') return values.length
  if (values.length === 0) return 0
  if (kind === 'sum') return values.reduce((a, b) => a + b, 0)
  if (kind === 'avg') return values.reduce((a, b) => a + b, 0) / values.length
  if (kind === 'min') return Math.min(...values)
  return Math.max(...values)
}

function evaluateFooter(col: TableCol, allRows: Record<string, unknown>[]): number | null {
  const raw = col.footer
  if (!raw) return null
  const bare = raw.trim().toLowerCase()

  // Fast path: a bare aggregator aggregates this column's own field. Pulled
  // through resolvePath so dot-path columns (`invoice.grand_total`,
  // `tax_code.code`) total correctly without needing the field name to be
  // a valid JS identifier.
  if ((AGGREGATORS as readonly string[]).includes(bare)) {
    const values = allRows.map((row) => {
      const v = resolvePath(row, col.field)
      const n = typeof v === 'number' ? v : Number(v)
      return Number.isFinite(n) ? n : 0
    })
    return aggregate(bare as Aggregator, values)
  }

  const fieldSet = new Set(columns.value.map((c) => c.field))
  const expr = raw

  const replacements: { token: string; value: number }[] = []
  let idx = 0
  const placeholderForm = expr.replace(AGG_RE, (_, name: string, inner: string) => {
    const kind = name.toLowerCase() as Aggregator
    const compiled = compileRowExpression(inner, fieldSet)
    if (!compiled) return '0'
    const values = allRows.map((row) => compiled(row))
    const result = aggregate(kind, values)
    const token = `__agg_${idx++}__`
    replacements.push({ token, value: result })
    return token
  })

  try {
    const tokens = replacements.map((r) => r.token)
    const fn = new Function(...tokens, `"use strict"; return (${placeholderForm});`) as (
      ...args: number[]
    ) => unknown
    const result = fn(...replacements.map((r) => r.value))
    const n = typeof result === 'number' ? result : Number(result)
    return Number.isFinite(n) ? n : null
  } catch (e) {
    console.error(`[Table] failed to evaluate footer expression: ${raw}`, e)
    return null
  }
}

const hasFooter = computed(() => columns.value.some((c) => !!c.footer))

interface FooterCell {
  text: string
  alignRight: boolean
}

function renderFooter(col: TableCol): FooterCell | null {
  if (!col.footer) return null
  const n = evaluateFooter(col, rows.value)
  if (n === null) return { text: '—', alignRight: true }
  const dec = col.decimalsField
    ? decimalsFor(col, source.value as Record<string, unknown>)
    : (col.decimals ?? 0)
  const formatted = new Intl.NumberFormat(undefined, {
    minimumFractionDigits: dec,
    maximumFractionDigits: dec,
  }).format(n)
  if (col.type === 'money') {
    const code = col.currencyField
      ? (resolvePath(source.value, col.currencyField) as string | undefined)
      : null
    return { text: code ? `${formatted} ${code}` : formatted, alignRight: true }
  }
  return { text: formatted, alignRight: true }
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
    <div class="overflow-x-auto rounded-md border border-paper-200 bg-card">
     <div class="min-w-max">
      <!-- Header -->
      <div
        class="grid border-b border-paper-200 bg-paper-75"
        :style="{ gridTemplateColumns: gridTemplate }"
      >
        <div
          v-for="col in columns"
          :key="col.field"
          class="flex h-8 items-center border-e border-paper-200 px-2.5 text-[10px] font-semibold tracking-[0.08em] text-ink-400 uppercase last:border-e-0"
          :class="col.alignRight ? 'justify-end' : 'justify-start'"
        >
          {{ col.label }}
        </div>
      </div>

      <!-- Empty -->
      <div
        v-if="rows.length === 0"
        class="flex h-10 items-center justify-center px-2.5 text-sm text-ink-400"
      >
        {{ emptyMessage }}
      </div>

      <!-- Rows -->
      <div
        v-for="(row, rowIdx) in rows"
        :key="rowIdx"
        class="grid border-b border-paper-100 last:border-b-0 hover:bg-paper-50"
        :style="{ gridTemplateColumns: gridTemplate }"
      >
        <div
          v-for="col in columns"
          :key="col.field"
          class="flex h-8 items-center border-e border-paper-100 px-2.5 text-sm tabular-nums last:border-e-0"
          :class="[
            col.alignRight ? 'justify-end' : 'justify-start',
            renderCell(col, row).kind === 'empty' ? 'text-ink-400' : 'text-ink-800',
          ]"
        >
          <template v-if="renderCell(col, row).kind === 'badge'">
            <Badge variant="outline" :class="badgeToneMap[renderCell(col, row).badgeKey ?? 'neutral']">
              {{ renderCell(col, row).text }}
            </Badge>
          </template>
          <template v-else>
            {{ renderCell(col, row).text }}
          </template>
        </div>
      </div>

      <!-- Footer -->
      <div
        v-if="hasFooter && rows.length > 0"
        class="grid border-t border-paper-200 bg-paper-75"
        :style="{ gridTemplateColumns: gridTemplate }"
      >
        <div
          v-for="col in columns"
          :key="col.field"
          class="flex h-9 items-center border-e border-paper-200 px-2.5 text-sm font-semibold tabular-nums text-ink-800 last:border-e-0"
          :class="col.alignRight ? 'justify-end' : 'justify-start'"
        >
          {{ renderFooter(col)?.text ?? '' }}
        </div>
      </div>
     </div>
    </div>
  </FieldWrapper>
</template>
