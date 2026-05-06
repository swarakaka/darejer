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
  return key
    .replace(/_/g, ' ')
    .replace(/\b\w/g, (c) => c.toUpperCase())
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
export function buildCsv(rows: Row[], columns: string[], totals?: Totals | null): string {
  const lines: string[] = []
  lines.push(columns.map((c) => escapeCsv(humanize(c))).join(','))

  for (const row of rows) {
    lines.push(columns.map((c) => escapeCsv(row[c])).join(','))
  }

  if (totals) {
    const totalsRow = columns.map((c, idx) => {
      if (idx === 0 && (totals[c] === undefined || totals[c] === null)) {
        return escapeCsv('Total')
      }
      const v = totals[c]
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
  return value
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
    || 'report'
}
