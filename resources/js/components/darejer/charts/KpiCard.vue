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

const trendClass = computed(() => {
    switch (props.trend) {
        case 'up':   return 'text-success-600'
        case 'down': return 'text-danger-600'
        default:     return 'text-ink-400'
    }
})
</script>

<template>
    <component
        :is="href ? Link : 'div'"
        :href="href ?? undefined"
        class="block bg-white border border-paper-200 rounded-md p-4 hover:border-paper-300 transition-colors no-underline"
    >
        <div v-if="eyebrow" class="text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-400 tabular-nums mb-1">
            {{ eyebrow }}
        </div>
        <div class="text-xs text-ink-500 mb-2">{{ label }}</div>
        <div class="flex items-baseline justify-between gap-3">
            <div class="font-serif text-2xl text-ink-900 tabular-nums leading-none">
                {{ formatted }}
            </div>
            <div v-if="delta || trendIcon" class="flex items-center gap-1 text-xs" :class="trendClass">
                <component :is="trendIcon" v-if="trendIcon" class="w-3.5 h-3.5" />
                <span v-if="delta">{{ delta }}</span>
            </div>
        </div>
    </component>
</template>
