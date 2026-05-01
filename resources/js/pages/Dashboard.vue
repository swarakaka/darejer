<script setup lang="ts">
import AppLayout       from '@/layouts/AppLayout.vue'
import AppBreadcrumbs  from '@/components/darejer/AppBreadcrumbs.vue'
import KpiCard         from '@/components/darejer/charts/KpiCard.vue'
import LineChart       from '@/components/darejer/charts/LineChart.vue'
import BarChart        from '@/components/darejer/charts/BarChart.vue'
import DoughnutChart   from '@/components/darejer/charts/DoughnutChart.vue'
import { Activity, BarChart3, PieChart, LineChart as LineIcon, LayoutDashboard, ArrowRight } from 'lucide-vue-next'
import useTranslation  from '@/composables/useTranslation'

defineOptions({ layout: AppLayout })

const { __, resolveTranslatable } = useTranslation()

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

const chartIcon = (type: 'line' | 'bar' | 'doughnut') => {
    if (type === 'line')     return LineIcon
    if (type === 'bar')      return BarChart3
    return PieChart
}
</script>

<template>
    <div class="flex flex-col h-full overflow-hidden bg-paper-100">

        <!-- Page header — refined hero with subtle gradient and accent rail -->
        <header class="shrink-0 relative bg-card border-b border-paper-200 overflow-hidden">
            <!-- Decorative dotted background -->
            <div
                class="absolute inset-0 opacity-[0.35] pointer-events-none"
                style="
                    background-image: radial-gradient(circle at 1px 1px, var(--color-paper-200) 1px, transparent 0);
                    background-size: 20px 20px;
                "
            />
            <!-- Hero gradient wash on the trailing edge -->
            <div class="absolute inset-y-0 inset-e-0 w-2/3 bg-gradient-to-s from-brand-50/60 via-white/0 to-transparent pointer-events-none" />

            <div class="relative flex items-start justify-between gap-6 px-6 pt-5 pb-5">
                <div class="flex items-start gap-3 min-w-0">
                    <div class="flex flex-col min-w-0">
                        <AppBreadcrumbs class="mb-2" />
                        <div class="flex items-baseline gap-2.5">
                            <h1 class="text-[28px] leading-[1.05] tracking-[-0.02em] text-ink-900 font-semibold">
                                {{ title }}
                            </h1>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Scrolling body -->
        <div class="flex-1 overflow-y-auto px-6 py-6 space-y-6">

            <!-- KPI strip -->
            <section
                v-if="kpis?.length"
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
                v-if="charts?.length"
                class="grid grid-cols-1 lg:grid-cols-2 gap-4"
            >
                <article
                    v-for="(c, i) in charts"
                    :key="`chart-${i}`"
                    class="group relative bg-card border border-paper-200 rounded-md
                           shadow-[0_1px_0_rgba(0,0,0,0.02)] overflow-hidden
                           hover:border-paper-300 hover:shadow-[0_4px_14px_-4px_rgba(0,0,0,0.08)] transition-all duration-150"
                    :class="(c.span ?? 1) === 2 ? 'lg:col-span-2' : ''"
                >
                    <header
                        class="flex items-center justify-between gap-3 px-5 h-11
                               border-b border-paper-200 bg-gradient-to-b from-paper-75 to-card"
                    >
                        <div class="flex items-center gap-2.5 min-w-0">
                            <span
                                class="inline-flex items-center justify-center w-6 h-6 rounded-sm
                                       bg-brand-50 text-brand-600 ring-1 ring-inset ring-brand-100"
                            >
                                <component :is="chartIcon(c.type)" class="w-3.5 h-3.5" />
                            </span>
                            <h2 class="text-[13px] font-semibold text-ink-900 tracking-tight truncate">
                                {{ resolveTranslatable(c.title) }}
                            </h2>
                        </div>
                        <span class="text-[10px] font-bold uppercase tracking-[0.12em] text-ink-400">
                            {{ c.type }}
                        </span>
                    </header>

                    <div class="p-5">
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
                </article>
            </section>

            <!-- Side-by-side list panels -->
            <section
                v-if="lists?.length"
                class="grid grid-cols-1 lg:grid-cols-2 gap-4"
            >
                <article
                    v-for="(panel, pi) in lists"
                    :key="`list-${pi}`"
                    class="group relative bg-card border border-paper-200 rounded-md overflow-hidden
                           shadow-[0_1px_0_rgba(0,0,0,0.02)]
                           hover:border-paper-300 hover:shadow-[0_4px_14px_-4px_rgba(0,0,0,0.08)] transition-all duration-150"
                >
                    <header class="flex items-center justify-between px-5 h-11 border-b border-paper-200 bg-gradient-to-b from-paper-75 to-card">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <span
                                class="inline-flex items-center justify-center w-6 h-6 rounded-sm
                                       bg-brand-50 text-brand-600 ring-1 ring-inset ring-brand-100"
                            >
                                <Activity class="w-3.5 h-3.5" />
                            </span>
                            <h2 class="text-[13px] font-semibold text-ink-900 tracking-tight truncate">
                                {{ resolveTranslatable(panel.title) }}
                            </h2>
                            <span class="text-[10.5px] font-semibold tabular-nums text-ink-400 ms-1">
                                {{ panel.rows.length }}
                            </span>
                        </div>
                        <a
                            v-if="panel.href"
                            :href="panel.href"
                            class="inline-flex items-center gap-1 text-[11px] font-semibold text-brand-700 hover:text-brand-800 transition-colors group/link"
                        >
                            {{ __('View all') }}
                            <ArrowRight class="w-3 h-3 transition-transform group-hover/link:translate-x-0.5 rtl:rotate-180 rtl:group-hover/link:-translate-x-0.5" />
                        </a>
                    </header>

                    <div v-if="panel.rows.length" class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-paper-200 bg-paper-75/60">
                                    <th
                                        v-for="col in panel.columns"
                                        :key="col.key"
                                        class="px-5 py-2 text-[10px] font-bold uppercase tracking-[0.12em] text-ink-500"
                                        :class="col.align === 'right' ? 'text-end' : 'text-start'"
                                    >
                                        {{ resolveTranslatable(col.label) }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(row, ri) in panel.rows"
                                    :key="`row-${ri}`"
                                    class="relative border-t border-paper-100 first:border-t-0 hover:bg-brand-50/40 transition-colors duration-75"
                                >
                                    <td
                                        v-for="(col, ci) in panel.columns"
                                        :key="col.key"
                                        class="px-5 py-2.5 text-[13px] text-ink-700 tabular-nums relative"
                                        :class="[
                                            col.align === 'right' ? 'text-end' : 'text-start',
                                            ci === 0 ? 'font-medium text-ink-900' : '',
                                        ]"
                                    >
                                        <!-- Leading accent bar on hover (first column only) -->
                                        <span
                                            v-if="ci === 0"
                                            class="absolute inset-y-0 start-0 w-0.5 bg-brand-500 scale-y-0 group-hover/row:scale-y-100 transition-transform"
                                        />
                                        {{ row[col.key] != null ? resolveTranslatable(row[col.key]) : '—' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Empty state for list panel -->
                    <div
                        v-else
                        class="flex flex-col items-center justify-center gap-1.5 py-12 px-5 text-center bg-paper-75/40"
                    >
                        <div class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-paper-100 text-ink-400 ring-1 ring-paper-200">
                            <Activity class="w-4 h-4" />
                        </div>
                        <p class="text-[12.5px] font-medium text-ink-600">{{ __('No data') }}</p>
                    </div>
                </article>
            </section>

            <!-- Empty state — no data of any kind -->
            <div
                v-if="!kpis?.length && !charts?.length && !lists?.length"
                class="relative flex flex-col items-center justify-center text-center py-20 px-6 rounded-md bg-card border border-dashed border-paper-300 overflow-hidden"
            >
                <!-- Pattern background -->
                <div
                    class="absolute inset-0 opacity-[0.5] pointer-events-none"
                    style="
                        background-image: radial-gradient(circle at 1px 1px, var(--color-paper-200) 1px, transparent 0);
                        background-size: 18px 18px;
                    "
                />
                <div class="relative flex flex-col items-center gap-3">
                    <div
                        class="inline-flex items-center justify-center w-14 h-14 rounded-md
                               bg-gradient-to-br from-brand-50 to-brand-100 text-brand-700
                               ring-1 ring-inset ring-brand-200/60 shadow-[0_2px_8px_-3px_rgba(0,120,212,0.3)]"
                    >
                        <LayoutDashboard class="w-6 h-6" />
                    </div>
                    <p class="text-[14px] font-semibold text-ink-800 tracking-tight">{{ __('Dashboard is empty') }}</p>
                    <p class="text-[12px] text-ink-500 max-w-md">
                        {{ __('Wire props from your DashboardController to populate this view.') }}
                    </p>
                </div>
            </div>

        </div>
    </div>
</template>
