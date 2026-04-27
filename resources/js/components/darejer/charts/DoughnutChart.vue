<script setup lang="ts">
import { computed } from 'vue'
import { Doughnut } from 'vue-chartjs'
import { brand, baseOptions } from './chartSetup'

interface Props {
    labels:  string[]
    values:  number[]
    colors?: string[]
    height?: number
    cutout?: string
}

const props = withDefaults(defineProps<Props>(), { height: 260, cutout: '60%' })

const data = computed(() => ({
    labels: props.labels,
    datasets: [{
        data: props.values,
        backgroundColor: props.colors ?? brand.palette,
        borderColor: '#fff',
        borderWidth: 2,
    }],
}))

const opts = computed(() => ({
    ...baseOptions,
    cutout: props.cutout,
}))
</script>

<template>
    <div :style="{ height: height + 'px' }">
        <Doughnut :data="data" :options="opts" />
    </div>
</template>
