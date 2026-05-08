<script setup lang="ts">
import { computed } from 'vue'
import { Badge } from '@/components/ui/badge'
import FieldWrapper from '@/components/darejer/FieldWrapper.vue'
import useTranslation from '@/composables/useTranslation'
import type { DarejerComponent } from '@/types/darejer'

type DisplayType = 'text' | 'badge' | 'date' | 'datetime' | 'number' | 'money' | 'boolean'

type BadgeVariant = 'default' | 'secondary' | 'destructive' | 'outline'

const props = defineProps<{
  component: DarejerComponent
  record: Record<string, unknown>
  errors: Record<string, string>
  formData?: Record<string, unknown>
}>()

const { __, resolveTranslatable } = useTranslation()

const source = computed(() => props.formData ?? props.record)

// Translatable fields go through the shared resolver (locale → default →
// first non-empty); non-translatable values pass straight through so that
// numbers, booleans, dates etc. keep their native type for downstream
// formatters (date/number/money/badge).
//
// `name` may be a dot path (`warehouse.name`, `cashier.username`) so eager-
// loaded relations can be displayed without flattening on the controller.
const rawValue = computed<unknown>(() => {
  const v = resolvePath(source.value, props.component.name)
  return props.component.translatable ? resolveTranslatable(v) : v
})

const displayType = computed<DisplayType>(
  () => (props.component.displayType as DisplayType) ?? 'text',
)

const isEmpty = computed(
  () => rawValue.value === null || rawValue.value === undefined || rawValue.value === '',
)

const emptyText = computed<string>(() => (props.component.emptyText as string | undefined) ?? '—')

// ── Date / DateTime ──────────────────────────────────────────────────────────
// Server-side toArray() serializes dates as ISO strings ('2026-04-25' or
// '2026-04-25T00:00:00.000000Z'). When PHP supplies a `dateFormat` (via
// Display::date('Y-m-d') / Display::dateTime('Y/m/d H:i')), honor it verbatim
// using PHP-style tokens so the rendered format is consistent across locales.
// Falls back to a locale-aware Intl format when no `dateFormat` is provided.
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

const dateFormat = computed<string | null>(
  () => (props.component.dateFormat as string | undefined) ?? null,
)

const dateFormatted = computed(() => {
  if (isEmpty.value) return ''
  const d = new Date(String(rawValue.value))
  if (Number.isNaN(d.getTime())) return String(rawValue.value)
  if (dateFormat.value) return formatPhpDate(d, dateFormat.value)
  return new Intl.DateTimeFormat(undefined, {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  }).format(d)
})

const dateTimeFormatted = computed(() => {
  if (isEmpty.value) return ''
  const d = new Date(String(rawValue.value))
  if (Number.isNaN(d.getTime())) return String(rawValue.value)
  if (dateFormat.value) return formatPhpDate(d, dateFormat.value)
  return new Intl.DateTimeFormat(undefined, {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  }).format(d)
})

function resolvePath(obj: Record<string, unknown>, path: string): unknown {
  return path
    .split('.')
    .reduce<unknown>(
      (acc, key) =>
        acc && typeof acc === 'object' ? (acc as Record<string, unknown>)[key] : undefined,
      obj,
    )
}

// ── Number / Money ───────────────────────────────────────────────────────────
// Money columns may declare `decimalsField` (a record path like
// `currency.minor_units`) so a single Display config formats IQD as 0dp and
// USD as 2dp without per-controller math. Fall back to the static `decimals`.
const decimals = computed<number>(() => {
  const path = props.component.decimalsField as string | undefined
  if (path) {
    const v = resolvePath(source.value, path)
    if (typeof v === 'number') return v
    if (typeof v === 'string' && v !== '') {
      const n = Number(v)
      if (Number.isFinite(n)) return n
    }
  }
  return (props.component.decimals as number | undefined) ?? 0
})

const numberFormatted = computed(() => {
  if (isEmpty.value) return ''
  const n = Number(rawValue.value)
  if (Number.isNaN(n)) return String(rawValue.value)
  return new Intl.NumberFormat(undefined, {
    minimumFractionDigits: decimals.value,
    maximumFractionDigits: decimals.value,
  }).format(n)
})

const moneyCurrencyCode = computed<string | null>(() => {
  const field = props.component.currencyField as string | undefined
  if (!field) return null
  const cur = resolvePath(source.value, field)
  return cur ? String(cur) : null
})

// ── Boolean ──────────────────────────────────────────────────────────────────
const booleanState = computed<boolean>(() => {
  const v = rawValue.value
  if (typeof v === 'boolean') return v
  if (typeof v === 'number') return v !== 0
  if (typeof v === 'string') return ['1', 'true', 'yes', 'on'].includes(v.toLowerCase())
  return false
})

const booleanLabel = computed(() =>
  booleanState.value
    ? ((props.component.booleanTrueLabel as string | undefined) ?? __('Yes'))
    : ((props.component.booleanFalseLabel as string | undefined) ?? __('No')),
)

// ── Badge variant resolution ─────────────────────────────────────────────────
// Map our semantic variants ('success', 'warning', 'danger', 'info', 'neutral')
// to the shadcn-vue Badge variants we already have, plus a tone class for the
// subtle/colored look DataGrid badges already use.
const badgeVariantMap: Record<string, BadgeVariant> = {
  success: 'outline',
  warning: 'outline',
  danger: 'outline',
  info: 'outline',
  neutral: 'outline',
}

const badgeToneMap: Record<string, string> = {
  success: 'bg-success-50 text-success-700 border-success-100',
  warning: 'bg-warning-50 text-warning-700 border-warning-100',
  danger: 'bg-danger-50 text-danger-700 border-danger-100',
  info: 'bg-brand-50 text-brand-700 border-brand-100',
  neutral: 'bg-paper-100 text-ink-500 border-paper-200',
}

const badgeKey = computed<string>(() => {
  const map = props.component.badgeMap as Record<string, string> | null | undefined
  if (!map || isEmpty.value) return 'neutral'
  return map[String(rawValue.value)] ?? 'neutral'
})

const badgeVariant = computed<BadgeVariant>(() => badgeVariantMap[badgeKey.value] ?? 'outline')

const badgeToneClass = computed<string>(() => badgeToneMap[badgeKey.value] ?? badgeToneMap.neutral)

// Translated label sent from PHP via `Display::badge(EnumClass::class)`. Falls
// back to the raw value when no labels map was provided (e.g. plain array form).
const badgeLabel = computed<string>(() => {
  const labels = props.component.badgeLabels as Record<string, string> | null | undefined
  if (labels && rawValue.value !== null && rawValue.value !== undefined) {
    const hit = labels[String(rawValue.value)]
    if (hit) return hit
  }
  return String(rawValue.value ?? '')
})
</script>

<template>
  <FieldWrapper :component="component" :record="record" :errors="errors" :form-data="formData">
    <div class="flex min-h-9 items-center text-sm text-ink-800">
      <!-- Empty -->
      <span v-if="isEmpty && displayType !== 'boolean'" class="text-ink-400">
        {{ emptyText }}
      </span>

      <!-- Badge -->
      <Badge v-else-if="displayType === 'badge'" :variant="badgeVariant" :class="badgeToneClass">
        {{ badgeLabel }}
      </Badge>

      <!-- Boolean -->
      <Badge
        v-else-if="displayType === 'boolean'"
        variant="outline"
        :class="
          booleanState
            ? `border-success-100 bg-success-50 text-success-700`
            : `border-paper-200 bg-paper-100 text-ink-500`
        "
      >
        {{ booleanLabel }}
      </Badge>

      <!-- Date -->
      <span v-else-if="displayType === 'date'" class="tabular-nums">
        {{ dateFormatted }}
      </span>

      <!-- Date+Time -->
      <span v-else-if="displayType === 'datetime'" class="tabular-nums">
        {{ dateTimeFormatted }}
      </span>

      <!-- Number -->
      <span v-else-if="displayType === 'number'" class="tabular-nums">
        <span v-if="component.prefix" class="me-1 text-ink-500">{{ component.prefix }}</span>
        {{ numberFormatted }}
        <span v-if="component.suffix" class="ms-1 text-ink-500">{{ component.suffix }}</span>
      </span>

      <!-- Money -->
      <span v-else-if="displayType === 'money'" class="tabular-nums">
        <span v-if="component.prefix" class="me-1 text-ink-500">{{ component.prefix }}</span>
        {{ numberFormatted }}
        <span v-if="moneyCurrencyCode" class="ms-1 text-ink-500">{{ moneyCurrencyCode }}</span>
        <span v-else-if="component.suffix" class="ms-1 text-ink-500">{{ component.suffix }}</span>
      </span>

      <!-- Plain text (default) -->
      <span v-else>
        <span v-if="component.prefix" class="me-1 text-ink-500">{{ component.prefix }}</span>
        {{ rawValue }}
        <span v-if="component.suffix" class="ms-1 text-ink-500">{{ component.suffix }}</span>
      </span>
    </div>
  </FieldWrapper>
</template>
