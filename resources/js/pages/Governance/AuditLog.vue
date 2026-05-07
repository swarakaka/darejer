<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import AppBreadcrumbs from '@/components/darejer/AppBreadcrumbs.vue'
import {
  Sheet,
  SheetContent,
  SheetHeader,
  SheetTitle,
  SheetDescription,
} from '@/components/ui/sheet'
import { Inbox, SlidersHorizontal, X, AlertTriangle } from 'lucide-vue-next'
import useTranslation from '@/composables/useTranslation'

defineOptions({ layout: AppLayout })

const { __ } = useTranslation()

interface AuditRow {
  id: number
  event: string
  subject_type: string | null
  subject_id: number | null
  causer_id: number | null
  causer: string | null
  reason: string | null
  summary: string | null
  payload: Record<string, unknown> | null
  ip: string | null
  user_agent: string | null
  created_at: string | null
}

interface Filters {
  event: string
  subject_type: string
  subject_id: string
  causer_id: string
  from: string
  to: string
}

const props = defineProps<{
  title: string
  rows: AuditRow[]
  total: number
  truncated: boolean
  rowLimit: number
  filters: Filters
  eventOptions: string[]
  subjectTypeOptions: string[]
}>()

const filterValues = ref<Filters>({ ...props.filters })
const showFilters = ref(true)
const selected = ref<AuditRow | null>(null)
const sheetOpen = ref(false)

const activeFilterCount = computed(() => {
  let n = 0
  if (filterValues.value.event) n++
  if (filterValues.value.subject_type) n++
  if (filterValues.value.subject_id) n++
  if (filterValues.value.causer_id) n++
  return n
})

function navigate() {
  const params: Record<string, string> = {}
  for (const [k, v] of Object.entries(filterValues.value)) {
    if (v !== '' && v !== null && v !== undefined) {
      params[k] = String(v)
    }
  }
  router.get(window.location.pathname, params, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}

let filterTimer: ReturnType<typeof setTimeout>
function onFilterChange() {
  clearTimeout(filterTimer)
  filterTimer = setTimeout(navigate, 400)
}

function resetFilters() {
  filterValues.value = {
    event: '',
    subject_type: '',
    subject_id: '',
    causer_id: '',
    from: filterValues.value.from,
    to: filterValues.value.to,
  }
  navigate()
}

function openRow(row: AuditRow) {
  selected.value = row
  sheetOpen.value = true
}

function eventBadgeClass(event: string): string {
  // Last segment of dot-delimited event name controls the badge tint:
  //   *.created → success, *.updated → info, *.deleted → danger
  const verb = event.split('.').pop() ?? ''
  if (verb === 'created' || verb === 'opened')
    return 'bg-success-50 text-success-700 border-success-100'
  if (verb === 'updated' || verb === 'changed') return 'bg-brand-50 text-brand-700 border-brand-100'
  if (verb === 'deleted' || verb === 'reversed' || verb === 'closed')
    return 'bg-danger-50 text-danger-700 border-danger-100'
  if (verb === 'approved' || verb === 'won')
    return 'bg-success-50 text-success-700 border-success-100'
  if (verb === 'rejected' || verb === 'lost')
    return 'bg-danger-50 text-danger-700 border-danger-100'
  return 'bg-paper-100 text-ink-600 border-paper-200'
}

function shortType(type: string | null): string {
  if (!type) return '—'
  const parts = type.split('\\')
  return parts[parts.length - 1] ?? type
}

function formatDate(iso: string | null): string {
  if (!iso) return '—'
  try {
    const d = new Date(iso)
    const yyyy = d.getFullYear()
    const mm = String(d.getMonth() + 1).padStart(2, '0')
    const dd = String(d.getDate()).padStart(2, '0')
    const hh = String(d.getHours()).padStart(2, '0')
    const mi = String(d.getMinutes()).padStart(2, '0')
    const ss = String(d.getSeconds()).padStart(2, '0')
    return `${yyyy}-${mm}-${dd} ${hh}:${mi}:${ss}`
  } catch {
    return iso
  }
}

const prettyPayload = computed(() => {
  if (!selected.value?.payload) return ''
  try {
    return JSON.stringify(selected.value.payload, null, 2)
  } catch {
    return String(selected.value.payload)
  }
})

watch(
  () => props.filters,
  (next) => {
    filterValues.value = { ...next }
  },
)
</script>

<template>
  <div class="flex h-full flex-col overflow-hidden bg-paper-50">
    <!-- Page title -->
    <header class="shrink-0 border-b border-paper-200 bg-card">
      <div class="flex items-start justify-between gap-6 px-6 pt-5 pb-4">
        <div class="flex min-w-0 flex-col">
          <AppBreadcrumbs class="mb-3" />
          <h1 class="text-2xl leading-[1.1] font-semibold tracking-tight text-ink-900">
            {{ title }}
          </h1>
          <p class="mt-1.5 text-sm text-ink-500 tabular-nums">
            {{ __(':n events recorded', { n: total }) }}
          </p>
        </div>

        <button
          type="button"
          class="flex h-9 items-center gap-1.5 rounded-md border border-paper-300 bg-card px-3 text-sm font-medium text-ink-700 shadow-xs transition-colors hover:border-paper-400 hover:bg-paper-50"
          @click="showFilters = !showFilters"
        >
          <SlidersHorizontal class="h-3.5 w-3.5" />
          {{ __('Filters') }}
          <span
            v-if="activeFilterCount > 0"
            class="inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-brand-600 px-1 text-[9px] font-bold text-white tabular-nums"
          >
            {{ activeFilterCount }}
          </span>
        </button>
      </div>
    </header>

    <!-- Content -->
    <div class="flex-1 overflow-y-auto px-6 py-6">
      <!-- Filter bar -->
      <div
        v-if="showFilters"
        class="mb-4 flex flex-wrap items-end gap-3 rounded-lg border border-paper-200 bg-card p-4 shadow-xs"
      >
        <div class="flex min-w-[12rem] flex-col gap-1.5">
          <label class="text-[11px] font-semibold tracking-[0.1em] text-ink-500 uppercase">{{
            __('Event')
          }}</label>
          <input
            v-model="filterValues.event"
            type="text"
            list="audit-event-options"
            :placeholder="__('e.g. document.posted')"
            class="h-9 rounded-md border border-paper-300 bg-card px-3 text-sm transition-colors placeholder:text-ink-400 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/15 focus:outline-none"
            @input="onFilterChange"
          />
          <datalist id="audit-event-options">
            <option v-for="ev in eventOptions" :key="ev" :value="ev" />
          </datalist>
        </div>

        <div class="flex min-w-[12rem] flex-col gap-1.5">
          <label class="text-[11px] font-semibold tracking-[0.1em] text-ink-500 uppercase">{{
            __('Subject type')
          }}</label>
          <select
            v-model="filterValues.subject_type"
            class="h-9 rounded-md border border-paper-300 bg-card px-2.5 text-sm transition-colors focus:border-brand-500 focus:ring-2 focus:ring-brand-500/15 focus:outline-none"
            @change="onFilterChange"
          >
            <option value="">{{ __('All') }}</option>
            <option v-for="t in subjectTypeOptions" :key="t" :value="t">{{ shortType(t) }}</option>
          </select>
        </div>

        <div class="flex w-32 flex-col gap-1.5">
          <label class="text-[11px] font-semibold tracking-[0.1em] text-ink-500 uppercase">{{
            __('Subject id')
          }}</label>
          <input
            v-model="filterValues.subject_id"
            type="number"
            class="h-9 rounded-md border border-paper-300 bg-card px-3 text-sm tabular-nums transition-colors focus:border-brand-500 focus:ring-2 focus:ring-brand-500/15 focus:outline-none"
            @input="onFilterChange"
          />
        </div>

        <div class="flex w-32 flex-col gap-1.5">
          <label class="text-[11px] font-semibold tracking-[0.1em] text-ink-500 uppercase">{{
            __('User id')
          }}</label>
          <input
            v-model="filterValues.causer_id"
            type="number"
            class="h-9 rounded-md border border-paper-300 bg-card px-3 text-sm tabular-nums transition-colors focus:border-brand-500 focus:ring-2 focus:ring-brand-500/15 focus:outline-none"
            @input="onFilterChange"
          />
        </div>

        <div class="flex flex-col gap-1.5">
          <label class="text-[11px] font-semibold tracking-[0.1em] text-ink-500 uppercase">{{
            __('From')
          }}</label>
          <input
            v-model="filterValues.from"
            type="date"
            class="h-9 rounded-md border border-paper-300 bg-card px-3 text-sm transition-colors focus:border-brand-500 focus:ring-2 focus:ring-brand-500/15 focus:outline-none"
            @change="onFilterChange"
          />
        </div>

        <div class="flex flex-col gap-1.5">
          <label class="text-[11px] font-semibold tracking-[0.1em] text-ink-500 uppercase">{{
            __('To')
          }}</label>
          <input
            v-model="filterValues.to"
            type="date"
            class="h-9 rounded-md border border-paper-300 bg-card px-3 text-sm transition-colors focus:border-brand-500 focus:ring-2 focus:ring-brand-500/15 focus:outline-none"
            @change="onFilterChange"
          />
        </div>

        <button
          v-if="activeFilterCount > 0"
          type="button"
          class="flex h-9 items-center gap-1.5 rounded-md px-3 text-sm font-medium text-ink-500 transition-colors hover:bg-paper-100 hover:text-ink-800"
          @click="resetFilters"
        >
          <X class="h-3.5 w-3.5" />
          {{ __('Clear') }} ({{ activeFilterCount }})
        </button>
      </div>

      <!-- Truncation warning -->
      <div
        v-if="truncated"
        class="mb-4 flex items-start gap-2 rounded-md border border-warning-100 bg-warning-50 px-3 py-2.5 text-xs text-warning-700"
      >
        <AlertTriangle class="mt-px h-4 w-4 shrink-0" />
        <span class="leading-relaxed">{{
          __('Showing the most recent :n events. Narrow the filters to see older entries.', {
            n: rowLimit,
          })
        }}</span>
      </div>

      <!-- Table card -->
      <div class="overflow-hidden rounded-lg border border-paper-200 bg-card shadow-xs">
        <div class="flex items-center gap-2 border-b border-paper-200 bg-paper-50 px-4 py-2.5">
          <span class="text-xs font-medium text-ink-500 tabular-nums">
            {{ __(':n events', { n: total }) }}
          </span>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full border-collapse">
            <thead>
              <tr class="border-b border-paper-200 bg-paper-50">
                <th class="h-10 w-44 px-4 text-start whitespace-nowrap">
                  <span class="text-[10px] font-semibold tracking-[0.12em] text-ink-500 uppercase">
                    {{ __('When') }}
                  </span>
                </th>
                <th class="h-10 px-4 text-start whitespace-nowrap">
                  <span class="text-[10px] font-semibold tracking-[0.12em] text-ink-500 uppercase">
                    {{ __('What happened') }}
                  </span>
                </th>
                <th class="h-10 px-4 text-start whitespace-nowrap">
                  <span class="text-[10px] font-semibold tracking-[0.12em] text-ink-500 uppercase">
                    {{ __('User') }}
                  </span>
                </th>
                <th class="h-10 px-4 text-start whitespace-nowrap">
                  <span class="text-[10px] font-semibold tracking-[0.12em] text-ink-500 uppercase">
                    {{ __('Reason') }}
                  </span>
                </th>
                <th class="h-10 w-28 px-4 text-start whitespace-nowrap">
                  <span class="text-[10px] font-semibold tracking-[0.12em] text-ink-500 uppercase">
                    {{ __('IP') }}
                  </span>
                </th>
              </tr>
            </thead>

            <tbody>
              <tr v-if="rows.length === 0">
                <td colspan="5" class="px-3 py-12 text-center">
                  <div class="flex flex-col items-center gap-2 text-ink-400">
                    <div
                      class="flex h-12 w-12 items-center justify-center rounded-full bg-paper-100"
                    >
                      <Inbox class="h-5 w-5 text-ink-400" />
                    </div>
                    <span class="text-sm font-medium text-ink-700">{{
                      __('No audit events match the current filters.')
                    }}</span>
                  </div>
                </td>
              </tr>

              <tr
                v-for="row in rows"
                :key="row.id"
                class="cursor-pointer border-b border-paper-100 transition-colors duration-75 last:border-b-0 hover:bg-paper-50"
                @click="openRow(row)"
              >
                <td class="h-10 px-4 text-sm whitespace-nowrap text-ink-700 tabular-nums">
                  {{ formatDate(row.created_at) }}
                </td>
                <td class="h-10 px-4 text-sm text-ink-800">
                  <template v-if="row.summary">
                    <span class="text-ink-800">{{ row.summary }}</span>
                  </template>
                  <template v-else>
                    <span
                      class="inline-flex items-center rounded-sm border px-1.5 py-0.5 text-[10px] font-semibold tracking-wide whitespace-nowrap uppercase"
                      :class="eventBadgeClass(row.event)"
                    >
                      {{ row.event }}
                    </span>
                    <span v-if="row.subject_type" class="ms-2 text-ink-700">
                      {{ shortType(row.subject_type)
                      }}<span v-if="row.subject_id" class="text-ink-400 tabular-nums">
                        #{{ row.subject_id }}</span
                      >
                    </span>
                  </template>
                </td>
                <td class="h-10 px-4 text-sm text-ink-800">
                  {{ row.causer ?? (row.causer_id ? `#${row.causer_id}` : '—') }}
                </td>
                <td class="h-10 px-4 text-sm text-ink-600">
                  <span class="block max-w-xs truncate">{{ row.reason ?? '—' }}</span>
                </td>
                <td class="h-10 px-4 text-xs text-ink-500 tabular-nums">
                  {{ row.ip ?? '—' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Slideover with full event details -->
    <Sheet :open="sheetOpen" @update:open="sheetOpen = $event">
      <SheetContent class="flex w-full flex-col gap-0 overflow-hidden p-0 sm:max-w-xl" side="right">
        <SheetHeader class="shrink-0 border-b border-paper-200 bg-paper-50 px-5 py-4 text-start">
          <SheetTitle class="text-base font-semibold tracking-tight text-ink-900">
            {{ __('Audit Event') }}
            <span v-if="selected" class="font-normal text-ink-400 tabular-nums"
              >#{{ selected.id }}</span
            >
          </SheetTitle>
          <SheetDescription v-if="selected" class="text-xs text-ink-500 tabular-nums">
            {{ formatDate(selected.created_at) }}
          </SheetDescription>
        </SheetHeader>

        <div v-if="selected" class="min-h-0 flex-1 space-y-5 overflow-y-auto px-5 py-4">
          <section v-if="selected.summary">
            <p class="text-sm leading-relaxed text-ink-800">
              {{ selected.summary }}
            </p>
          </section>

          <section>
            <h3 class="mb-2 text-[10px] font-semibold tracking-[0.08em] text-ink-400 uppercase">
              {{ __('Details') }}
            </h3>
            <dl class="grid grid-cols-[8rem_1fr] gap-x-3 gap-y-2 text-sm">
              <dt class="text-ink-500">{{ __('Event') }}</dt>
              <dd>
                <span
                  class="inline-flex items-center rounded-sm border px-1.5 py-0.5 text-[10px] font-semibold tracking-wide uppercase"
                  :class="eventBadgeClass(selected.event)"
                >
                  {{ selected.event }}
                </span>
              </dd>

              <dt class="text-ink-500">{{ __('Subject') }}</dt>
              <dd class="break-all text-ink-800">
                <template v-if="selected.subject_type">
                  {{ selected.subject_type }}
                  <span v-if="selected.subject_id" class="text-ink-400 tabular-nums">
                    #{{ selected.subject_id }}
                  </span>
                </template>
                <span v-else class="text-ink-400">—</span>
              </dd>

              <dt class="text-ink-500">{{ __('User') }}</dt>
              <dd class="text-ink-800">
                {{ selected.causer ?? '—' }}
                <span v-if="selected.causer_id" class="text-ink-400 tabular-nums">
                  #{{ selected.causer_id }}
                </span>
              </dd>

              <dt class="text-ink-500">{{ __('IP') }}</dt>
              <dd class="text-ink-800 tabular-nums">{{ selected.ip ?? '—' }}</dd>

              <dt class="text-ink-500">{{ __('User agent') }}</dt>
              <dd class="text-xs break-all text-ink-700">{{ selected.user_agent ?? '—' }}</dd>
            </dl>
          </section>

          <section v-if="selected.reason">
            <h3 class="mb-2 text-[10px] font-semibold tracking-[0.08em] text-ink-400 uppercase">
              {{ __('Reason') }}
            </h3>
            <p class="text-sm whitespace-pre-wrap text-ink-800">{{ selected.reason }}</p>
          </section>

          <section v-if="selected.payload">
            <h3 class="mb-2 text-[10px] font-semibold tracking-[0.08em] text-ink-400 uppercase">
              {{ __('Payload') }}
            </h3>
            <pre
              class="overflow-x-auto rounded-md border border-paper-200 bg-paper-75 p-3 font-mono text-xs leading-relaxed break-all whitespace-pre-wrap text-ink-800"
              >{{ prettyPayload }}</pre
            >
          </section>
        </div>
      </SheetContent>
    </Sheet>
  </div>
</template>
