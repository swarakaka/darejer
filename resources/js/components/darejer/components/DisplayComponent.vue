<script setup lang="ts">
import { computed }     from 'vue'
import { Badge }        from '@/components/ui/badge'
import FieldWrapper     from '@/components/darejer/FieldWrapper.vue'
import { useLanguages } from '@/composables/useLanguages'
import useTranslation   from '@/composables/useTranslation'
import type { DarejerComponent } from '@/types/darejer'

type DisplayType = 'text' | 'badge' | 'date' | 'datetime' | 'number' | 'money' | 'boolean'

type BadgeVariant = 'default' | 'secondary' | 'destructive' | 'outline'

const props = defineProps<{
    component: DarejerComponent
    record:    Record<string, unknown>
    errors:    Record<string, string>
    formData?: Record<string, unknown>
}>()

const { __ } = useTranslation()
const { currentLocale, defaultLanguage } = useLanguages()

const source = computed(() => props.formData ?? props.record)

// Resolve a translatable JSON value (`{en: '...', ar: '...'}`) to the user's
// active locale, falling back to the configured default language and finally
// the first non-empty entry. Plain strings/numbers pass through untouched.
function resolveTranslatable(value: unknown): unknown {
    if (value === null || value === undefined) return value
    if (typeof value !== 'object' || Array.isArray(value)) return value
    const map = value as Record<string, unknown>
    return map[currentLocale.value]
        ?? map[defaultLanguage.value]
        ?? Object.values(map).find(v => v !== null && v !== undefined && v !== '')
        ?? ''
}

const rawValue = computed<unknown>(() => {
    const v = source.value[props.component.name]
    return props.component.translatable ? resolveTranslatable(v) : v
})

const displayType = computed<DisplayType>(
    () => (props.component.displayType as DisplayType) ?? 'text'
)

const isEmpty = computed(() =>
    rawValue.value === null
    || rawValue.value === undefined
    || rawValue.value === ''
)

const emptyText = computed<string>(() =>
    (props.component.emptyText as string | undefined) ?? '—'
)

// ── Date / DateTime ──────────────────────────────────────────────────────────
// Server-side toArray() serializes dates as ISO strings ('2026-04-25' or
// '2026-04-25T00:00:00.000000Z'). Use Intl.DateTimeFormat in the user's locale
// for display so the format follows the active language.
const dateFormatted = computed(() => {
    if (isEmpty.value) return ''
    const d = new Date(String(rawValue.value))
    if (Number.isNaN(d.getTime())) return String(rawValue.value)
    return new Intl.DateTimeFormat(undefined, {
        year:  'numeric',
        month: '2-digit',
        day:   '2-digit',
    }).format(d)
})

const dateTimeFormatted = computed(() => {
    if (isEmpty.value) return ''
    const d = new Date(String(rawValue.value))
    if (Number.isNaN(d.getTime())) return String(rawValue.value)
    return new Intl.DateTimeFormat(undefined, {
        year:   'numeric',
        month:  '2-digit',
        day:    '2-digit',
        hour:   '2-digit',
        minute: '2-digit',
    }).format(d)
})

// ── Number / Money ───────────────────────────────────────────────────────────
const decimals = computed<number>(
    () => (props.component.decimals as number | undefined) ?? 0
)

const numberFormatted = computed(() => {
    if (isEmpty.value) return ''
    const n = Number(rawValue.value)
    if (Number.isNaN(n)) return String(rawValue.value)
    return new Intl.NumberFormat(undefined, {
        minimumFractionDigits: decimals.value,
        maximumFractionDigits: decimals.value,
    }).format(n)
})

function resolvePath(obj: Record<string, unknown>, path: string): unknown {
    return path.split('.').reduce<unknown>(
        (acc, key) => (acc && typeof acc === 'object'
            ? (acc as Record<string, unknown>)[key]
            : undefined),
        obj,
    )
}

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
    if (typeof v === 'number')  return v !== 0
    if (typeof v === 'string')  return ['1', 'true', 'yes', 'on'].includes(v.toLowerCase())
    return false
})

const booleanLabel = computed(() => booleanState.value
    ? ((props.component.booleanTrueLabel as string | undefined) ?? __('Yes'))
    : ((props.component.booleanFalseLabel as string | undefined) ?? __('No'))
)

// ── Badge variant resolution ─────────────────────────────────────────────────
// Map our semantic variants ('success', 'warning', 'danger', 'info', 'neutral')
// to the shadcn-vue Badge variants we already have, plus a tone class for the
// subtle/colored look DataGrid badges already use.
const badgeVariantMap: Record<string, BadgeVariant> = {
    success:    'outline',
    warning:    'outline',
    danger:     'outline',
    info:       'outline',
    neutral:    'outline',
}

const badgeToneMap: Record<string, string> = {
    success: 'bg-success-50 text-success-700 border-success-100',
    warning: 'bg-warning-50 text-warning-700 border-warning-100',
    danger:  'bg-danger-50 text-danger-700 border-danger-100',
    info:    'bg-brand-50 text-brand-700 border-brand-100',
    neutral: 'bg-paper-100 text-ink-500 border-paper-200',
}

const badgeKey = computed<string>(() => {
    const map = (props.component.badgeMap as Record<string, string> | null | undefined)
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
        <div class="min-h-9 flex items-center text-sm text-ink-800">

            <!-- Empty -->
            <span v-if="isEmpty && displayType !== 'boolean'" class="text-ink-400">
                {{ emptyText }}
            </span>

            <!-- Badge -->
            <Badge
                v-else-if="displayType === 'badge'"
                :variant="badgeVariant"
                :class="badgeToneClass"
            >
                {{ badgeLabel }}
            </Badge>

            <!-- Boolean -->
            <Badge
                v-else-if="displayType === 'boolean'"
                variant="outline"
                :class="booleanState ? 'bg-success-50 text-success-700 border-success-100' : 'bg-paper-100 text-ink-500 border-paper-200'"
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
                <span v-if="component.prefix" class="text-ink-500 me-1">{{ component.prefix }}</span>
                {{ numberFormatted }}
                <span v-if="component.suffix" class="text-ink-500 ms-1">{{ component.suffix }}</span>
            </span>

            <!-- Money -->
            <span v-else-if="displayType === 'money'" class="tabular-nums">
                <span v-if="component.prefix" class="text-ink-500 me-1">{{ component.prefix }}</span>
                {{ numberFormatted }}
                <span v-if="moneyCurrencyCode" class="text-ink-500 ms-1">{{ moneyCurrencyCode }}</span>
                <span v-else-if="component.suffix" class="text-ink-500 ms-1">{{ component.suffix }}</span>
            </span>

            <!-- Plain text (default) -->
            <span v-else>
                <span v-if="component.prefix" class="text-ink-500 me-1">{{ component.prefix }}</span>
                {{ rawValue }}
                <span v-if="component.suffix" class="text-ink-500 ms-1">{{ component.suffix }}</span>
            </span>

        </div>
    </FieldWrapper>
</template>
