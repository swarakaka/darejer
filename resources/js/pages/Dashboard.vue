<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { Activity, BarChart3, PieChart, LineChart as LineIcon, LayoutDashboard, ArrowRight } from 'lucide-vue-next'
import AppBreadcrumbs from '@/components/darejer/AppBreadcrumbs.vue'
import BarChart from '@/components/darejer/charts/BarChart.vue'
import DoughnutChart from '@/components/darejer/charts/DoughnutChart.vue'
import KpiCard from '@/components/darejer/charts/KpiCard.vue'
import LineChart from '@/components/darejer/charts/LineChart.vue'
import useTranslation from '@/composables/useTranslation'
import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const { __, resolveTranslatable } = useTranslation()

interface Kpi {
  label: string
  value: string | number
  delta?: string | null
  trend?: 'up' | 'down' | 'flat' | null
  href?: string | null
  format?: 'amount' | 'count' | 'plain'
  currency?: string | null
  eyebrow?: string | null
}

interface ChartConfig {
  type: 'line' | 'bar' | 'doughnut'
  title: string
  span?: 1 | 2 // grid columns out of 2
  height?: number
  // Line / Bar
  labels?: string[]
  series?: { label: string; data: number[]; color?: string; fill?: boolean }[]
  horizontal?: boolean
  stacked?: boolean
  smooth?: boolean
  area?: boolean
  // Doughnut
  values?: number[]
  colors?: string[]
  cutout?: string
}

interface ListPanel {
  title: string
  columns: { key: string; label: string; align?: 'left' | 'right' }[]
  rows: Record<string, string | number | null>[]
  href?: string
}

withDefaults(
  defineProps<{
    title?: string
    kpis?: Kpi[]
    charts?: ChartConfig[]
    lists?: ListPanel[]
  }>(),
  {
    title: 'Dashboard',
    kpis: () => [],
    charts: () => [],
    lists: () => [],
  },
)

const chartIcon = (type: 'line' | 'bar' | 'doughnut') => {
  if (type === 'line') return LineIcon
  if (type === 'bar') return BarChart3
  return PieChart
}
</script>

<template>
  <Head :title="title" />
  <div class="bg-paper-100 flex h-full flex-col overflow-hidden">
    <!-- Page header — refined hero with subtle gradient and accent rail -->
    <header class="border-paper-200 bg-card relative shrink-0 overflow-hidden border-b">
      <!-- Decorative dotted background -->
      <div
        class="pointer-events-none absolute inset-0 opacity-[0.35]"
        style="
          background-image: radial-gradient(circle at 1px 1px, var(--color-paper-200) 1px, transparent 0);
          background-size: 20px 20px;
        "
      />
      <!-- Hero gradient wash on the trailing edge -->
      <div
        class="bg-linear-to-s from-brand-50/60 pointer-events-none absolute inset-y-0 inset-e-0 w-2/3 via-white/0 to-transparent"
      />

      <div class="relative flex items-start justify-between gap-6 px-6 pt-5 pb-5">
        <div class="flex min-w-0 items-start gap-3">
          <div class="flex min-w-0 flex-col">
            <AppBreadcrumbs class="mb-2" />
            <div class="flex items-baseline gap-2.5">
              <h1 class="text-ink-900 text-[28px] leading-[1.05] font-semibold tracking-[-0.02em]">
                {{ title }}
              </h1>
            </div>
          </div>
        </div>
      </div>
    </header>

    <!-- Scrolling body -->
    <div class="scrollbar-darejer flex-1 scrollbar-gutter-stable space-y-6 overflow-y-auto px-6 py-6">
      <!-- KPI strip -->
      <section v-if="kpis?.length" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7">
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
      <section v-if="charts?.length" class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <article
          v-for="(c, i) in charts"
          :key="`chart-${i}`"
          class="group border-paper-200 bg-card hover:border-paper-300 relative overflow-hidden rounded-md border shadow-[0_1px_0_rgba(0,0,0,0.02)] transition-all duration-150 hover:shadow-[0_4px_14px_-4px_rgba(0,0,0,0.08)]"
          :class="(c.span ?? 1) === 2 ? 'lg:col-span-2' : ''"
        >
          <header
            class="border-paper-200 from-paper-75 to-card flex h-11 items-center justify-between gap-3 border-b bg-gradient-to-b px-5"
          >
            <div class="flex min-w-0 items-center gap-2.5">
              <span
                class="bg-brand-50 text-brand-600 ring-brand-100 inline-flex h-6 w-6 items-center justify-center rounded-sm ring-1 ring-inset"
              >
                <component :is="chartIcon(c.type)" class="h-3.5 w-3.5" />
              </span>
              <h2 class="text-ink-900 truncate text-[13px] font-semibold tracking-tight">
                {{ resolveTranslatable(c.title) }}
              </h2>
            </div>
            <span class="text-ink-400 text-[10px] font-bold tracking-[0.12em] uppercase">
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
      <section v-if="lists?.length" class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <article
          v-for="(panel, pi) in lists"
          :key="`list-${pi}`"
          class="group border-paper-200 bg-card hover:border-paper-300 relative overflow-hidden rounded-md border shadow-[0_1px_0_rgba(0,0,0,0.02)] transition-all duration-150 hover:shadow-[0_4px_14px_-4px_rgba(0,0,0,0.08)]"
        >
          <header
            class="border-paper-200 from-paper-75 to-card flex h-11 items-center justify-between border-b bg-gradient-to-b px-5"
          >
            <div class="flex min-w-0 items-center gap-2.5">
              <span
                class="bg-brand-50 text-brand-600 ring-brand-100 inline-flex h-6 w-6 items-center justify-center rounded-sm ring-1 ring-inset"
              >
                <Activity class="h-3.5 w-3.5" />
              </span>
              <h2 class="text-ink-900 truncate text-[13px] font-semibold tracking-tight">
                {{ resolveTranslatable(panel.title) }}
              </h2>
              <span class="text-ink-400 ms-1 text-[10.5px] font-semibold tabular-nums">
                {{ panel.rows.length }}
              </span>
            </div>
            <Link
              v-if="panel.href"
              :href="panel.href"
              class="group/link text-brand-700 hover:text-brand-800 inline-flex items-center gap-1 text-[11px] font-semibold transition-colors"
            >
              {{ __('View all') }}
              <ArrowRight
                class="h-3 w-3 transition-transform group-hover/link:translate-x-0.5 rtl:rotate-180 rtl:group-hover/link:-translate-x-0.5"
              />
            </Link>
          </header>

          <div v-if="panel.rows.length" class="scrollbar-darejer overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-paper-200 bg-paper-75/60 border-b">
                  <th
                    v-for="col in panel.columns"
                    :key="col.key"
                    class="text-ink-500 px-5 py-2 text-[10px] font-bold tracking-[0.12em] uppercase"
                    :class="col.align === 'right' ? `text-end` : `text-start`"
                  >
                    {{ resolveTranslatable(col.label) }}
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(row, ri) in panel.rows"
                  :key="`row-${ri}`"
                  class="border-paper-100 hover:bg-brand-50/40 relative border-t transition-colors duration-75 first:border-t-0"
                >
                  <td
                    v-for="(col, ci) in panel.columns"
                    :key="col.key"
                    class="text-ink-700 relative px-5 py-2.5 text-[13px] tabular-nums"
                    :class="[
                      col.align === 'right' ? 'text-end' : `text-start`,
                      ci === 0 ? `text-ink-900 font-medium` : '',
                    ]"
                  >
                    <!-- Leading accent bar on hover (first column only) -->
                    <span
                      v-if="ci === 0"
                      class="bg-brand-500 absolute inset-y-0 start-0 w-0.5 scale-y-0 transition-transform group-hover/row:scale-y-100"
                    />
                    {{ row[col.key] != null ? resolveTranslatable(row[col.key]) : '—' }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Empty state for list panel -->
          <div v-else class="bg-paper-75/40 flex flex-col items-center justify-center gap-1.5 px-5 py-12 text-center">
            <div
              class="bg-paper-100 text-ink-400 ring-paper-200 inline-flex h-9 w-9 items-center justify-center rounded-full ring-1"
            >
              <Activity class="h-4 w-4" />
            </div>
            <p class="text-ink-600 text-[12.5px] font-medium">{{ __('No data') }}</p>
          </div>
        </article>
      </section>

      <!-- Empty state — no data of any kind -->
      <div
        v-if="!kpis?.length && !charts?.length && !lists?.length"
        class="border-paper-300 bg-card relative flex flex-col items-center justify-center overflow-hidden rounded-md border border-dashed px-6 py-20 text-center"
      >
        <!-- Pattern background -->
        <div
          class="pointer-events-none absolute inset-0 opacity-[0.5]"
          style="
            background-image: radial-gradient(circle at 1px 1px, var(--color-paper-200) 1px, transparent 0);
            background-size: 18px 18px;
          "
        />
        <div class="relative flex flex-col items-center gap-3">
          <div
            class="from-brand-50 to-brand-100 text-brand-700 ring-brand-200/60 inline-flex h-14 w-14 items-center justify-center rounded-md bg-gradient-to-br shadow-[0_2px_8px_-3px_rgba(0,120,212,0.3)] ring-1 ring-inset"
          >
            <LayoutDashboard class="h-6 w-6" />
          </div>
          <p class="text-ink-800 text-[14px] font-semibold tracking-tight">
            {{ __('Dashboard is empty') }}
          </p>
          <p class="text-ink-500 max-w-md text-[12px]">
            {{ __('Wire props from your DashboardController to populate this view.') }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
