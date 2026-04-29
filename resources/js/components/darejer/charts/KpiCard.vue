<script setup lang="ts">
import { computed } from 'vue'
import { Link }     from '@inertiajs/vue3'
import { ArrowUpRight, ArrowDownRight, Minus } from 'lucide-vue-next'

interface Props {
    label:    string
    value:    string | number
    delta?:   string | null
    trend?:   'up' | 'down' | 'flat' | null
    href?:    string | null
    format?:  'amount' | 'count' | 'plain'
    currency?:string | null
    eyebrow?: string | null
}

const props = withDefaults(defineProps<Props>(), {
    delta: null, trend: null, href: null, format: 'plain',
    currency: null, eyebrow: null,
})

const formatted = computed(() => {
    const v = props.value
    if (props.format === 'count') {
        return typeof v === 'number'
            ? v.toLocaleString()
            : (parseInt(String(v), 10) || 0).toLocaleString()
    }
    if (props.format === 'amount') {
        const n = typeof v === 'number' ? v : parseFloat(String(v))
        if (Number.isNaN(n)) return String(v)
        const formatted = new Intl.NumberFormat(undefined, {
            minimumFractionDigits: 2, maximumFractionDigits: 2,
        }).format(n)
        return props.currency ? `${formatted} ${props.currency}` : formatted
    }
    return String(v)
})

const trendIcon = computed(() => {
    switch (props.trend) {
        case 'up':   return ArrowUpRight
        case 'down': return ArrowDownRight
        case 'flat': return Minus
        default:     return null
    }
})

const trendBgClass = computed(() => {
    switch (props.trend) {
        case 'up':   return 'bg-success-50 text-success-700 border-success-100'
        case 'down': return 'bg-danger-50 text-danger-700 border-danger-100'
        case 'flat': return 'bg-paper-100 text-ink-600 border-paper-200'
        default:     return 'bg-paper-100 text-ink-600 border-paper-200'
    }
})
</script>

<template>
    <component
        :is="href ? Link : 'div'"
        :href="href ?? undefined"
        class="group relative block bg-white border border-paper-200 rounded-lg p-4 shadow-xs hover:border-paper-300 hover:shadow-sm transition-all no-underline overflow-hidden"
    >
        <span class="absolute inset-y-0 inset-s-0 w-0.5 bg-brand-500 opacity-0 group-hover:opacity-100 transition-opacity" />

        <div v-if="eyebrow" class="text-[10px] font-semibold uppercase tracking-[0.16em] text-brand-700 tabular-nums mb-1.5">
            {{ eyebrow }}
        </div>
        <div class="text-[11px] font-semibold uppercase tracking-[0.1em] text-ink-500 mb-2">{{ label }}</div>

        <div class="flex items-end justify-between gap-3">
            <div class="text-2xl font-semibold text-ink-900 tabular-nums leading-none tracking-tight">
                {{ formatted }}
            </div>
            <div
                v-if="delta || trendIcon"
                class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-full border text-[10px] font-semibold tabular-nums"
                :class="trendBgClass"
            >
                <component :is="trendIcon" v-if="trendIcon" class="w-3 h-3" />
                <span v-if="delta">{{ delta }}</span>
            </div>
        </div>
    </component>
</template>
