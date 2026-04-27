<script setup lang="ts">
import AppLayout       from '@/layouts/AppLayout.vue'
import AppBreadcrumbs  from '@/components/darejer/AppBreadcrumbs.vue'
import KpiCard         from '@/components/darejer/charts/KpiCard.vue'
import LineChart       from '@/components/darejer/charts/LineChart.vue'
import BarChart        from '@/components/darejer/charts/BarChart.vue'
import DoughnutChart   from '@/components/darejer/charts/DoughnutChart.vue'

defineOptions({ layout: AppLayout })

interface Kpi {
    label:    string
    value:    string | number
    delta?:   string | null
    trend?:   'up' | 'down' | 'flat' | null
    href?:    string | null
    format?:  'amount' | 'count' | 'plain'
    currency?:string | null
    eyebrow?: string | null
}

interface ChartConfig {
    type:    'line' | 'bar' | 'doughnut'
    title:   string
    span?:   1 | 2          // grid columns out of 2
    height?: number
    // Line / Bar
    labels?: string[]
    series?: { label: string; data: number[]; color?: string; fill?: boolean }[]
    horizontal?: boolean
    stacked?:    boolean
    smooth?:     boolean
    area?:       boolean
    // Doughnut
    values?: number[]
    colors?: string[]
    cutout?: string
}

interface ListPanel {
    title:    string
    columns:  { key: string; label: string; align?: 'left' | 'right' }[]
    rows:     Record<string, string | number | null>[]
    href?:    string
}

withDefaults(defineProps<{
    title?:  string
    kpis?:   Kpi[]
    charts?: ChartConfig[]
    lists?:  ListPanel[]
}>(), {
    title:  'Dashboard',
    kpis:   () => [],
    charts: () => [],
    lists:  () => [],
})
</script>

<template>
    <div class="flex flex-col h-full overflow-hidden bg-paper-50">

        <!-- Page header -->
        <div class="flex items-start justify-between gap-6 px-6 pt-5 pb-4 border-b border-paper-200 shrink-0 bg-white">
            <div class="flex flex-col min-w-0">
                <AppBreadcrumbs class="mb-3" />
                <h1 class="font-serif text-[1.75rem] leading-[1.1] tracking-tight text-ink-900">
                    {{ title }}
                </h1>
            </div>
        </div>

        <!-- Scrolling body -->
        <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5">

            <!-- KPI strip -->
            <section
                v-if="kpis.length"
                class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7 gap-3"
            >
                <KpiCard
                    v-for="(k, i) in kpis"
                    :key="`kpi-${i}`"
                    :label="k.label"
                    :value="k.value"
                    :delta="k.delta"
                    :trend="k.trend"
                    :href="k.href"
                    :format="k.format"
                    :currency="k.currency"
                    :eyebrow="k.eyebrow"
                />
            </section>

            <!-- Charts grid -->
            <section
                v-if="charts.length"
                class="grid grid-cols-1 lg:grid-cols-2 gap-4"
            >
                <div
                    v-for="(c, i) in charts"
                    :key="`chart-${i}`"
                    class="bg-white border border-paper-200 rounded-md p-4"
                    :class="(c.span ?? 1) === 2 ? 'lg:col-span-2' : ''"
                >
                    <div class="flex items-baseline justify-between mb-3">
                        <h2 class="font-serif text-base text-ink-700">{{ c.title }}</h2>
                    </div>

                    <LineChart
                        v-if="c.type === 'line'"
                        :labels="c.labels ?? []"
                        :series="c.series ?? []"
                        :height="c.height ?? 260"
                        :smooth="c.smooth !== false"
                        :area="c.area === true"
                    />
                    <BarChart
                        v-else-if="c.type === 'bar'"
                        :labels="c.labels ?? []"
                        :series="c.series ?? []"
                        :height="c.height ?? 260"
                        :horizontal="c.horizontal === true"
                        :stacked="c.stacked === true"
                    />
                    <DoughnutChart
                        v-else-if="c.type === 'doughnut'"
                        :labels="c.labels ?? []"
                        :values="c.values ?? []"
                        :colors="c.colors"
                        :height="c.height ?? 260"
                        :cutout="c.cutout ?? '60%'"
                    />
                </div>
            </section>

            <!-- Side-by-side list panels -->
            <section
                v-if="lists.length"
                class="grid grid-cols-1 lg:grid-cols-2 gap-4"
            >
                <div
                    v-for="(panel, pi) in lists"
                    :key="`list-${pi}`"
                    class="bg-white border border-paper-200 rounded-md overflow-hidden"
                >
                    <div class="flex items-baseline justify-between px-4 py-3 border-b border-paper-200">
                        <h2 class="font-serif text-base text-ink-700">{{ panel.title }}</h2>
                        <a v-if="panel.href" :href="panel.href" class="text-xs text-brand-600 hover:text-brand-700">View all →</a>
                    </div>
                    <table v-if="panel.rows.length" class="w-full text-sm">
                        <thead class="bg-paper-50 text-[10px] uppercase tracking-[0.12em] text-ink-500">
                            <tr>
                                <th
                                    v-for="col in panel.columns"
                                    :key="col.key"
                                    class="px-4 py-2 font-semibold"
                                    :class="col.align === 'right' ? 'text-right' : 'text-left'"
                                >
                                    {{ col.label }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(row, ri) in panel.rows"
                                :key="`row-${ri}`"
                                class="border-t border-paper-100"
                            >
                                <td
                                    v-for="col in panel.columns"
                                    :key="col.key"
                                    class="px-4 py-2 text-ink-700 tabular-nums"
                                    :class="col.align === 'right' ? 'text-right' : 'text-left'"
                                >
                                    {{ row[col.key] ?? '—' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-else class="px-4 py-8 text-center text-sm text-ink-400">
                        No data
                    </div>
                </div>
            </section>

            <div v-if="!kpis.length && !charts.length && !lists.length" class="text-center text-ink-400 py-16 text-sm">
                Dashboard is empty. Wire props from your DashboardController.
            </div>

        </div>
    </div>
</template>
