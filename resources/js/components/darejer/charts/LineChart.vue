<script setup lang="ts">
import { computed } from 'vue'
import { Line }     from 'vue-chartjs'
import { brand, baseOptions } from './chartSetup'
import useTranslation from '@/composables/useTranslation'

interface Series { label: string; data: number[]; color?: string; fill?: boolean }

interface Props {
    labels:  string[]
    series:  Series[]
    height?: number
    smooth?: boolean
    area?:   boolean
}

const props = withDefaults(defineProps<Props>(), { height: 280, smooth: true, area: false })

const { resolveTranslatable } = useTranslation()

const data = computed(() => ({
    labels: props.labels.map(label => resolveTranslatable(label)),
    datasets: props.series.map((s, i) => {
        const color = s.color ?? brand.palette[i % brand.palette.length]
        const soft  = i === 0 ? brand.primarySoft : 'rgba(107,114,128,0.08)'
        return {
            label: resolveTranslatable(s.label),
            data:  s.data,
            borderColor: color,
            backgroundColor: props.area ? soft : color,
            tension: props.smooth ? 0.35 : 0,
            fill: props.area || s.fill === true,
            pointRadius: 2,
            pointHoverRadius: 4,
            borderWidth: 2,
        }
    }),
}))

const opts = computed(() => ({
    ...baseOptions,
    scales: {
        x: { grid: { display: false }, ticks: { font: { size: 10 } } },
        y: { grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 10 } } },
    },
}))
</script>

<template>
    <div :style="{ height: height + 'px' }">
        <Line :data="data" :options="opts" />
    </div>
</template>
