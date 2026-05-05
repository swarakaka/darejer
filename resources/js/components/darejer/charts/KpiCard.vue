<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { ArrowUpRight, ArrowDownRight, Minus } from 'lucide-vue-next'
import useTranslation from '@/composables/useTranslation'

interface Props {
  label: string
  value: string | number
  delta?: string | null
  trend?: 'up' | 'down' | 'flat' | null
  href?: string | null
  format?: 'amount' | 'count' | 'plain'
  currency?: string | null
  eyebrow?: string | null
}

const props = withDefaults(defineProps<Props>(), {
  delta: null,
  trend: null,
  href: null,
  format: 'plain',
  currency: null,
  eyebrow: null,
})

const { resolveTranslatable } = useTranslation()

const localizedLabel = computed(() => resolveTranslatable(props.label))
const localizedEyebrow = computed(() => (props.eyebrow ? resolveTranslatable(props.eyebrow) : null))

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
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    }).format(n)
    return props.currency ? `${formatted} ${props.currency}` : formatted
  }
  return String(v)
})

const trendIcon = computed(() => {
  switch (props.trend) {
    case 'up':
      return ArrowUpRight
    case 'down':
      return ArrowDownRight
    case 'flat':
      return Minus
    default:
      return null
  }
})

const trendStyles = computed(() => {
  switch (props.trend) {
    case 'up':
      return 'bg-success-50 text-success-700 ring-success-100'
    case 'down':
      return 'bg-danger-50 text-danger-700 ring-danger-100'
    case 'flat':
      return 'bg-paper-100 text-ink-600 ring-paper-200'
    default:
      return 'bg-paper-100 text-ink-600 ring-paper-200'
  }
})

const railColor = computed(() => {
  switch (props.trend) {
    case 'up':
      return 'bg-success-500'
    case 'down':
      return 'bg-danger-500'
    case 'flat':
      return 'bg-ink-400'
    default:
      return 'bg-brand-500'
  }
})
</script>

<template>
  <component
    :is="href ? Link : 'div'"
    :href="href ?? undefined"
    class="group relative isolate flex flex-col overflow-hidden rounded-md border border-paper-200 bg-card no-underline shadow-[0_1px_0_rgba(0,0,0,0.02)] transition-all duration-150 hover:border-paper-300 hover:shadow-[0_4px_12px_-4px_rgba(0,0,0,0.08),0_1px_0_rgba(0,0,0,0.02)]"
  >
    <!-- Top accent rail — bleeds in on hover -->
    <span
      class="absolute inset-x-0 top-0 h-0.5 origin-left scale-x-0 transition-transform duration-200 ease-out group-hover:scale-x-100"
      :class="railColor"
    />

    <!-- Background sheen — radial gradient that fades on hover -->
    <div
      class="absolute inset-0 -z-10 bg-linear-to-be from-card via-card to-paper-75/60 opacity-100 transition-opacity duration-200 group-hover:opacity-0"
    />
    <div
      class="absolute inset-0 -z-10 bg-linear-to-be from-card via-brand-50/30 to-paper-75/40 opacity-0 transition-opacity duration-200 group-hover:opacity-100"
    />

    <div class="flex flex-col gap-3 p-4">
      <!-- Eyebrow / Label row -->
      <div class="flex min-w-0 items-start justify-between gap-2">
        <div class="flex min-w-0 flex-col gap-1">
          <div
            v-if="localizedEyebrow"
            class="truncate text-[9px] font-bold tracking-[0.18em] text-brand-700 uppercase tabular-nums"
          >
            {{ localizedEyebrow }}
          </div>
          <div
            class="truncate text-[10.5px] font-semibold tracking-[0.12em] text-ink-500 uppercase"
          >
            {{ localizedLabel }}
          </div>
        </div>
        <div
          v-if="delta || trendIcon"
          class="inline-flex shrink-0 items-center gap-0.5 rounded-full px-1.5 py-[3px] text-[10px] leading-none font-bold tabular-nums ring-1 ring-inset"
          :class="trendStyles"
        >
          <component :is="trendIcon" v-if="trendIcon" class="h-3 w-3" />
          <span v-if="delta">{{ delta }}</span>
        </div>
      </div>

      <!-- Value -->
      <div
        class="text-[26px] leading-none font-semibold tracking-tight text-ink-900 tabular-nums transition-colors duration-150 group-hover:text-brand-700"
      >
        {{ formatted }}
      </div>
    </div>

    <!-- Footer rail — adds visual weight at the base -->
    <div
      class="mt-auto h-[3px] bg-paper-100 transition-colors duration-150 group-hover:bg-paper-150"
    >
      <span
        class="block h-full origin-left scale-x-0 transition-transform duration-300 ease-out group-hover:scale-x-100"
        :class="railColor"
      />
    </div>
  </component>
</template>
