<script setup lang="ts">
import { computed } from 'vue'
import { Bar }      from 'vue-chartjs'
import { brand, baseOptions } from './chartSetup'
import useTranslation from '@/composables/useTranslation'

interface Series { label: string; data: number[]; color?: string }

interface Props {
    labels:    string[]
    series:    Series[]
    height?:   number
    horizontal?: boolean
    stacked?:  boolean
}

const props = withDefaults(defineProps<Props>(), { height: 280, horizontal: false, stacked: false })

const { resolveTranslatable } = useTranslation()

const data = computed(() => ({
    labels: props.labels.map(label => resolveTranslatable(label)),
    datasets: props.series.map((s, i) => ({
        label: resolveTranslatable(s.label),
        data:  s.data,
        backgroundColor: s.color ?? brand.palette[i % brand.palette.length],
        borderRadius: 4,
        borderSkipped: false,
    })),
}))

const opts = computed(() => ({
    ...baseOptions,
    indexAxis: props.horizontal ? ('y' as const) : ('x' as const),
    scales: {
        x: { stacked: props.stacked, grid: { display: false }, ticks: { font: { size: 10 } } },
        y: { stacked: props.stacked, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 10 } } },
    },
}))
</script>

<template>
    <div :style="{ height: height + 'px' }">
        <Bar :data="data" :options="opts" />
    </div>
</template>
