/**
 * Shared utilities for rendering and exporting report tables.
 *
 * The Screen layer (Inertia ->with(['rows' => ..., 'totals' => ...])) hands
 * us untyped, row-shaped data. We auto-derive columns from the first row,
 * skip internal/foreign-key columns, detect numeric columns by sniffing
 * values, and format cells consistently — all without forcing each report
 * to declare schema on the frontend.
 *
 * ReportResults.vue uses these for rendering; Screen.vue uses the same
 * derivation for CSV export so the file mirrors the on-screen table.
 */

export type Row = Record<string, unknown>
export type Totals = Record<string, unknown>

export type ReportColumnDisplay = 'date' | 'datetime' | 'number' | 'money' | 'boolean' | 'plain'

export type ReportColumn = {
  field: string
  label: string
  align?: 'left' | 'right' | 'center'
  width?: string
  hidden?: boolean
  displayType?: ReportColumnDisplay
  dateFormat?: string
  decimals?: number
  currencyField?: string
}

const HIDDEN_KEY_PATTERNS = [/^id$/, /_id$/, /^_/]
const NUMERIC_RE = /^-?\d+(\.\d+)?$/

export function isHiddenKey(key: string): boolean {
  return HIDDEN_KEY_PATTERNS.some((re) => re.test(key))
}

export function deriveColumns(rows: Row[]): string[] {
  const first = rows[0]
  if (!first) {
    return []
  }
  return Object.keys(first).filter((k) => !isHiddenKey(k))
}

export function humanize(key: string): string {
  return key.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())
}

/**
 * Resolve a dot-notated path (e.g. `currency.code`) against a row.
 */
function resolvePath(row: Row, path: string): unknown {
  const parts = path.split('.')
  let cursor: unknown = row
  for (const part of parts) {
    if (cursor === null || cursor === undefined) {
      return undefined
    }
    cursor = (cursor as Record<string, unknown>)[part]
  }
  return cursor
}

export function isNumericValue(value: unknown): boolean {
  if (typeof value === 'number') {
    return true
  }
  return typeof value === 'string' && NUMERIC_RE.test(value)
}

export function isNumericColumn(rows: Row[], key: string): boolean {
  for (const row of rows) {
    const v = row[key]
    if (v === null || v === undefined || v === '') {
      continue
    }
    return isNumericValue(v)
  }
  return false
}

export function formatNumber(value: unknown): string {
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

export function formatDisplay(value: unknown, isNumeric: boolean): string {
  if (value === null || value === undefined || value === '') {
    return '—'
  }
  if (isNumeric && isNumericValue(value)) {
    return formatNumber(value)
  }
  return String(value)
}

function formatFixed(value: unknown, decimals: number): string {
  if (typeof value === 'number') {
    return value.toLocaleString(undefined, {
      minimumFractionDigits: decimals,
      maximumFractionDigits: decimals,
    })
  }
  if (typeof value === 'string' && NUMERIC_RE.test(value)) {
    const n = Number(value)
    if (Number.isFinite(n)) {
      return n.toLocaleString(undefined, {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
      })
    }
  }
  return String(value ?? '')
}

function formatDate(value: unknown, includeTime: boolean): string {
  if (typeof value !== 'string' && !(value instanceof Date)) {
    return String(value ?? '')
  }
  const d = value instanceof Date ? value : new Date(value)
  if (Number.isNaN(d.getTime())) {
    return String(value)
  }
  return includeTime ? d.toLocaleString() : d.toLocaleDateString()
}

/**
 * Format a cell using an explicit column descriptor. Falls back to the
 * row-sniffing `formatDisplay` when no display type is set.
 */
export function formatCell(value: unknown, column: ReportColumn, row: Row): string {
  if (value === null || value === undefined || value === '') {
    return '—'
  }

  switch (column.displayType) {
    case 'date':
      return formatDate(value, false)
    case 'datetime':
      return formatDate(value, true)
    case 'number':
      return formatFixed(value, column.decimals ?? 2)
    case 'money': {
      const formatted = formatFixed(value, column.decimals ?? 2)
      if (column.currencyField) {
        const code = resolvePath(row, column.currencyField)
        if (code) {
          return `${formatted} ${String(code)}`
        }
      }
      return formatted
    }
    case 'boolean':
      return value ? 'Yes' : 'No'
    case 'plain':
      return String(value)
    default:
      return formatDisplay(value, isNumericValue(value))
  }
}

/**
 * Visible columns from an explicit schema, filtering out hidden ones.
 */
export function visibleColumns(columns: ReportColumn[]): ReportColumn[] {
  return columns.filter((c) => !c.hidden)
}

export function isNumericColumnDef(column: ReportColumn): boolean {
  return column.displayType === 'number' || column.displayType === 'money'
}

/** Escape a single CSV field per RFC 4180 (quote when needed, double inner quotes). */
function escapeCsv(value: unknown): string {
  if (value === null || value === undefined) {
    return ''
  }
  const s = String(value)
  if (/[",\r\n]/.test(s)) {
    return `"${s.replace(/"/g, '""')}"`
  }
  return s
}

/**
 * Build a CSV string from rows and an optional totals row, prepending a UTF-8
 * BOM so Excel auto-detects the encoding instead of mojibaking Arabic text.
 *
 * For CSV we write *raw* numeric strings (not the locale-formatted display
 * strings) — that way Excel parses them as numbers. Display formatting is
 * for the screen; the file should be machine-friendly.
 */
export function buildCsv(rows: Row[], columns: string[] | ReportColumn[], totals?: Totals | null): string {
  const isSchema = columns.length > 0 && typeof columns[0] !== 'string'
  const schema = isSchema ? (columns as ReportColumn[]).filter((c) => !c.hidden) : null
  const fields: string[] = schema ? schema.map((c) => c.field) : (columns as string[])
  const headers: string[] = schema ? schema.map((c) => c.label) : (columns as string[]).map((c) => humanize(c))

  const lines: string[] = []
  lines.push(headers.map((h) => escapeCsv(h)).join(','))

  for (const row of rows) {
    lines.push(fields.map((f) => escapeCsv(row[f])).join(','))
  }

  if (totals) {
    const totalsRow = fields.map((f, idx) => {
      if (idx === 0 && (totals[f] === undefined || totals[f] === null)) {
        return escapeCsv('Total')
      }
      const v = totals[f]
      return v === undefined ? '' : escapeCsv(v)
    })
    lines.push(totalsRow.join(','))
  }

  return '﻿' + lines.join('\r\n')
}

/** Trigger a browser download of a string payload. */
export function downloadFile(filename: string, mime: string, data: string): void {
  const blob = new Blob([data], { type: `${mime};charset=utf-8` })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = filename
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
  URL.revokeObjectURL(url)
}

export function slugify(value: string): string {
  return (
    value
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '') || 'report'
  )
}
