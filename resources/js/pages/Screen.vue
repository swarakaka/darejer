<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3'
import { ChevronDown, Circle, Play, FileSpreadsheet, FileText } from 'lucide-vue-next'
import { ref, computed, watch } from 'vue'
import AppBreadcrumbs from '@/components/darejer/AppBreadcrumbs.vue'
import DarejerActions from '@/components/darejer/DarejerActions.vue'
import DarejerComponent from '@/components/darejer/DarejerComponent.vue'
import ReportResults from '@/components/darejer/ReportResults.vue'
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { useDarejerForm } from '@/composables/useDarejerForm'
import { evaluateDependOn } from '@/composables/useDependOn'
import useTranslation from '@/composables/useTranslation'
import { layouts } from '@/layouts/registry'
import { type ReportColumn, buildCsv, deriveColumns, downloadFile, slugify } from '@/lib/reportTable'
import type { ScreenProps, ScreenSection, ScreenTab, DarejerComponent as DarejerComponentType } from '@/types/darejer'

// Layout is picked at runtime from the registry using the `layout` prop sent
// by Screen::layout('minimal'). Defaults to 'app' (AppLayout) when no layout
// is requested. Inertia's single-arg layout callback receives page props.
defineOptions({
  layout: (props: { layout?: string | null }) => {
    const name = props.layout ?? 'app'
    return layouts[name] ?? layouts.app
  },
})

const { __ } = useTranslation()

const props = defineProps<ScreenProps & { sections?: ScreenSection[] | null; tabs?: ScreenTab[] | null }>()

// A screen renders as a dialog when the PHP side flagged it via ->dialog(),
// OR when the current URL has ?_dialog=1 — so any screen can be opened as a
// dialog from a ModalToggle action without changing its PHP definition.
const isDialog = computed(() => {
  if (props.dialog === true) return true
  if (typeof window === 'undefined') return false
  return new URL(window.location.href).searchParams.get('_dialog') === '1'
})

function closeDialog() {
  window.history.back()
}

defineExpose({ closeDialog })

// Find the Save action: determines the URL + method the form posts to.
const saveAction = computed(() => props.actions.find((a) => a.type === 'Save'))

// Darejer form — wraps Inertia useForm, collects component values,
// handles files + translatable fields, surfaces errors into FieldWrapper.
const {
  formData,
  errors: formErrors,
  processing,
  isDirty,
  updateField,
  submit,
  syncRecord,
  cancel,
} = useDarejerForm({
  url: (saveAction.value?.url as string) ?? '',
  method: (saveAction.value?.method?.toLowerCase() ?? 'post') as 'post' | 'put' | 'patch' | 'delete',
  components: props.components ?? [],
  record: (props.record ?? {}) as Record<string, unknown>,
  dialog: isDialog.value,
})

// Soft Inertia redirects between create → show (same Screen component) reuse
// this component instance — setup() doesn't re-run, so formData would stay
// bound to the pre-submit values. Re-sync whenever the record prop changes
// so the freshly-arrived record renders without a manual refresh.
watch(
  () => props.record,
  (record) => {
    syncRecord((record ?? {}) as Record<string, unknown>, props.components ?? [])
  },
)

// Merge server-sent errors (Inertia shared props) with useForm's own errors.
const mergedErrors = computed<Record<string, string>>(() => ({
  ...(props.errors ?? {}),
  ...formErrors.value,
}))

// ── Report mode ───────────────────────────────────────────────────────────
// Reports use the same Screen with `->with(['rows' => ..., 'totals' => ...])`.
// When `rows` is present on the page props the controller is rendering a
// report, so we surface an Apply button (filters → URL) and a results table.
const page = usePage<Record<string, unknown>>()

const reportRows = computed<Record<string, unknown>[] | null>(() => {
  const r = page.props.rows
  return Array.isArray(r) ? (r as Record<string, unknown>[]) : null
})

const reportTotals = computed<Record<string, unknown> | null>(() => {
  const t = page.props.totals
  return t && typeof t === 'object' ? (t as Record<string, unknown>) : null
})

const reportColumns = computed<ReportColumn[] | null>(() => {
  const c = page.props.reportColumns
  return Array.isArray(c) ? (c as ReportColumn[]) : null
})

const isReport = computed(() => reportRows.value !== null)

function applyFilters() {
  const params: Record<string, unknown> = {}
  for (const [key, value] of Object.entries(formData)) {
    if (value === null || value === undefined || value === '') {
      continue
    }
    params[key] = value
  }
  router.get(window.location.pathname, params as Record<string, string | number>, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}

const canExport = computed(() => isReport.value && reportRows.value !== null && reportRows.value.length > 0)

function exportFilenameStem(): string {
  const today = new Date().toISOString().slice(0, 10)
  return `${slugify(props.title)}-${today}`
}

function exportCsv() {
  if (!canExport.value || !reportRows.value) {
    return
  }
  const columns = reportColumns.value ?? deriveColumns(reportRows.value)
  const csv = buildCsv(reportRows.value, columns, reportTotals.value)
  downloadFile(`${exportFilenameStem()}.csv`, 'text/csv', csv)
}

function exportPdf() {
  if (!canExport.value) {
    return
  }
  // Browser-native print → user can save as PDF. The print stylesheet hides
  // the app chrome, action pane, and filter card so only the title + table
  // remain — see `print:hidden` utilities below and on AppLayout.
  window.print()
}

// ── Cascading reset ───────────────────────────────────────────────────────
// Precompute the set of fields that control some dependent component or
// section — typing into any other field skips the check entirely and stays
// fast. Running on every keystroke via a deep watcher was an O(n) hit per
// character for large forms.
const controllerFields = computed<Set<string>>(() => {
  const fields = new Set<string>()

  const collect = (rule: unknown) => {
    if (!rule || typeof rule !== 'object') return
    const r = rule as { field?: string; conditions?: { field: string }[] }
    if (r.field) fields.add(r.field)
    for (const c of r.conditions ?? []) {
      if (c.field) fields.add(c.field)
    }
  }

  for (const c of (props.components ?? []) as DarejerComponentType[]) {
    collect(c.dependOn)
  }
  for (const s of props.sections ?? []) {
    collect(s.dependOn)
  }
  for (const t of props.tabs ?? []) {
    collect(t.dependOn)
  }
  return fields
})

function cascadeResetHidden() {
  for (const c of (props.components ?? []) as DarejerComponentType[]) {
    if (!c.dependOn) continue

    const visible = evaluateDependOn(c.dependOn, formData)
    if (visible) continue

    const defaultVal = (c.default ?? null) as unknown
    if (formData[c.name] !== defaultVal) {
      updateField(c.name, defaultVal)
    }
  }
}

function handleFieldUpdate(name: string, value: unknown) {
  updateField(name, value)

  // Only iterate components when a *controller* field changed.
  if (controllerFields.value.has(name)) {
    cascadeResetHidden()
  }
}

/**
 * Combobox prefill: a child component (e.g. a Sales Order combobox with
 * `prefillFrom(...)`) returned a record from the server. Merge each key
 * into form state via the same updateField path so dirty-tracking and
 * cascading-reset behave normally.
 */
function handlePrefill(fields: Record<string, unknown>) {
  let cascade = false
  for (const [name, value] of Object.entries(fields)) {
    updateField(name, value)
    if (controllerFields.value.has(name)) cascade = true
  }
  if (cascade) cascadeResetHidden()
}

function isSectionVisible(section: ScreenSection): boolean {
  return evaluateDependOn(section.dependOn, formData)
}

function isTabVisible(tab: ScreenTab): boolean {
  return evaluateDependOn(tab.dependOn, formData)
}

const collapsed = ref<Record<string, boolean>>(
  Object.fromEntries((props.sections ?? []).map((s) => [s.key, s.collapsible ? (s.collapsed ?? false) : false])),
)

function toggleSection(key: string) {
  const section = (props.sections ?? []).find((s) => s.key === key)
  if (!section?.collapsible) {
    return
  }
  collapsed.value[key] = !collapsed.value[key]
}

const hasSections = computed(() => !!(props.sections && props.sections.length > 0))

const hasTabs = computed(() => !!(props.tabs && props.tabs.length > 0))

const visibleTabs = computed<ScreenTab[]>(() => (props.tabs ?? []).filter(isTabVisible))

const defaultTabValue = computed<string | undefined>(() => visibleTabs.value[0]?.name)

// Persist the active tab in localStorage, scoped per Screen by URL path.
// The path is locale-independent (the locale lives in the session, not the
// URL), so the same key resolves before and after a locale switch — and the
// stored value matches a current tab because `tab.name` is also stable.
//
// Normalize the path so every variant of the same resource form shares one
// key: drop numeric ID segments and the trailing `/create` / `/edit` action.
// Without this, `/items/create`, `/items/1/edit`, and `/items/2/edit` each
// get their own storage entry — so switching items or moving from create to
// edit silently resets the active tab.
const tabsPersistKey = computed<string | undefined>(() => {
  const url = page.url ?? ''
  const rawPath = url.split('?')[0] || '/'
  const path = rawPath
    .split('/')
    .filter((seg) => seg !== '' && !/^\d+$/.test(seg) && seg !== 'create' && seg !== 'edit')
    .join('/')
  return `screen:/${path}`
})

// Tab is "in error" when any of its components have a validation error
// in mergedErrors. Errors may be nested (e.g. "field.0.subfield"), so
// match by exact key or by "name." prefix.
function tabHasError(tab: ScreenTab): boolean {
  const errorKeys = Object.keys(mergedErrors.value)
  if (errorKeys.length === 0) {
    return false
  }
  for (const componentName of tab.components) {
    const prefix = `${componentName}.`
    for (const errorKey of errorKeys) {
      if (errorKey === componentName || errorKey.startsWith(prefix)) {
        return true
      }
    }
  }
  return false
}

function componentsForSection(section: ScreenSection) {
  return props.components.filter((c) => section.components.includes(c.name))
}

function componentsForTab(tab: ScreenTab) {
  return props.components.filter((c) => tab.components.includes(c.name))
}

const dialogSizeClass: Record<string, string> = {
  xs: 'sm:max-w-xs',
  sm: 'sm:max-w-md',
  md: 'sm:max-w-lg',
  lg: 'sm:max-w-3xl',
  xl: 'sm:max-w-5xl',
  full: 'sm:max-w-[95vw]',
}
</script>

<template>
  <Head :title="title" />
  <!-- ── Full-page mode ──────────────────────────────────────────── -->
  <template v-if="!isDialog">
    <div
      class="bg-paper-100 flex h-full flex-col overflow-hidden print:block print:h-auto print:overflow-visible print:bg-white"
    >
      <!-- Scrolling content -->
      <div
        :class="[
          'scrollbar-darejer flex-1 scrollbar-gutter-stable overflow-y-auto print:overflow-visible',
          fullWidth ? 'flex flex-col' : '',
        ]"
      >
        <!-- Page title — hero with subtle gradient -->
        <header class="border-paper-200 bg-card relative shrink-0 overflow-hidden border-b">
          <div
            class="pointer-events-none absolute inset-0 opacity-[0.35]"
            style="
              background-image: radial-gradient(circle at 1px 1px, var(--color-paper-200) 1px, transparent 0);
              background-size: 20px 20px;
            "
          />
          <div
            class="bg-linear-to-s from-brand-50/60 pointer-events-none absolute inset-y-0 inset-e-0 w-2/3 via-white/0 to-transparent"
          />

          <div class="relative flex flex-col gap-4 px-6 pt-5 pb-5">
            <div class="flex min-w-0 flex-col">
              <AppBreadcrumbs class="mb-2 print:hidden" />
              <h1 class="text-ink-900 text-[28px] leading-[1.05] font-semibold tracking-[-0.02em]">
                {{ title }}
              </h1>
            </div>
          </div>
        </header>
        <!-- Action Pane — under breadcrumbs and title -->
        <div class="flex flex-wrap items-center justify-end gap-1.5 px-6 pt-6 print:hidden">
          <span
            v-if="isDirty && !processing && !isReport"
            class="bg-warning-50 text-2xs text-warning-700 ring-warning-100 inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 font-bold tracking-[0.14em] uppercase shadow-[0_1px_0_rgba(0,0,0,0.02)] ring-1 ring-inset"
          >
            <span class="relative flex h-1.5 w-1.5">
              <span class="bg-warning-500 absolute inset-0 animate-ping rounded-full opacity-75" />
              <span class="bg-warning-500 relative h-1.5 w-1.5 rounded-full" />
            </span>
            {{ __('Unsaved changes') }}
          </span>
          <button
            v-if="isReport"
            type="button"
            class="bg-brand-600 hover:bg-brand-700 inline-flex h-8 items-center gap-1.5 rounded-md border border-transparent px-3 text-sm font-medium text-white shadow-sm transition-colors"
            @click="applyFilters"
          >
            <Play class="h-3.5 w-3.5 rtl:rotate-180" />
            {{ __('Apply') }}
          </button>
          <button
            v-if="canExport"
            type="button"
            class="border-paper-300 bg-card text-ink-700 hover:border-paper-400 hover:bg-paper-100 inline-flex h-8 items-center gap-1.5 rounded-md border px-3 text-sm font-medium shadow-xs transition-colors"
            @click="exportCsv"
          >
            <FileSpreadsheet class="h-3.5 w-3.5" />
            {{ __('Export CSV') }}
          </button>
          <button
            v-if="canExport"
            type="button"
            class="border-paper-300 bg-card text-ink-700 hover:border-paper-400 hover:bg-paper-100 inline-flex h-8 items-center gap-1.5 rounded-md border px-3 text-sm font-medium shadow-xs transition-colors"
            @click="exportPdf"
          >
            <FileText class="h-3.5 w-3.5" />
            {{ __('Export PDF') }}
          </button>
          <DarejerActions
            :actions="actions"
            placement="header"
            :form-data="formData"
            :processing="processing"
            :is-dirty="isDirty"
            :on-save="submit"
            :on-cancel="cancel"
          />
        </div>
        <!-- Body content -->
        <div :class="['flex-1', fullWidth ? 'flex min-h-0 flex-col' : `px-6 pt-5 pb-6`]">
          <!-- Full-width mode: no FastTab wrapper -->
          <template v-if="fullWidth">
            <div class="flex min-h-0 flex-1 flex-col">
              <DarejerComponent
                v-for="component in components"
                :key="component.name"
                :component="component"
                :record="record"
                :errors="mergedErrors"
                :form-data="formData"
                class="min-h-0 flex-1"
                @update="handleFieldUpdate"
                @prefill="handlePrefill"
              />
            </div>
          </template>

          <!-- Horizontal tabs / FastTabs -->
          <div v-else class="space-y-4">
            <template v-if="hasTabs">
              <Tabs
                :default-value="defaultTabValue"
                :persist-key="tabsPersistKey"
                :valid-values="visibleTabs.map((t) => t.name)"
                class="w-full"
              >
                <TabsList
                  class="scrollbar-darejer border-paper-200 bg-card h-auto w-full justify-start gap-0 overflow-x-auto rounded-md border p-1 shadow-[0_1px_0_rgba(0,0,0,0.02)]"
                >
                  <TabsTrigger
                    v-for="tab in visibleTabs"
                    :key="tab.name"
                    :value="tab.name"
                    :has-error="tabHasError(tab)"
                    class="text-ink-600 hover:text-ink-900 data-[state=active]:bg-brand-50 data-[state=active]:text-brand-700 px-4 py-2 text-[13px] font-semibold tracking-tight transition-colors data-[state=active]:rounded-[2px] data-[state=active]:shadow-[inset_0_-2px_0_var(--color-brand-500)]"
                  >
                    {{ tab.title }}
                  </TabsTrigger>
                </TabsList>
                <TabsContent v-for="tab in visibleTabs" :key="tab.name" :value="tab.name" class="mt-0">
                  <section
                    class="border-paper-200 bg-card relative overflow-hidden rounded-md border p-5 shadow-[0_1px_0_rgba(0,0,0,0.02)]"
                  >
                    <span
                      class="bg-linear-to-e from-brand-500 via-brand-400 absolute inset-x-0 top-0 h-0.5 to-transparent"
                    />
                    <div class="grid w-full grid-cols-1 gap-x-5 gap-y-5 sm:grid-cols-2 lg:grid-cols-3">
                      <DarejerComponent
                        v-for="component in componentsForTab(tab)"
                        :key="component.name"
                        :component="component"
                        :record="record"
                        :errors="mergedErrors"
                        :form-data="formData"
                        @update="handleFieldUpdate"
                        @prefill="handlePrefill"
                      />
                    </div>
                  </section>
                </TabsContent>
              </Tabs>
            </template>

            <!-- FastTab sections — numbered, refined headers -->
            <template v-if="hasSections">
              <section
                v-for="section in sections ?? []"
                v-show="isSectionVisible(section)"
                :key="section.key"
                class="group/section border-paper-200 bg-card relative overflow-hidden rounded-md border shadow-[0_1px_0_rgba(0,0,0,0.02)] transition-all duration-150"
                :class="
                  !collapsed[section.key] ? `border-paper-300 ring-brand-100/50 ring-1` : 'hover:border-paper-300'
                "
              >
                <header
                  v-if="section.title"
                  class="flex items-center justify-between gap-3 px-5 py-3 transition-colors select-none"
                  :class="[
                    !collapsed[section.key]
                      ? `border-paper-200 from-paper-75 to-card border-b bg-linear-to-b`
                      : 'bg-card',
                    section.collapsible ? `hover:bg-paper-75 cursor-pointer` : '',
                  ]"
                  :role="section.collapsible ? 'button' : undefined"
                  :aria-expanded="section.collapsible ? !collapsed[section.key] : undefined"
                  @click="toggleSection(section.key)"
                >
                  <div class="flex min-w-0 items-center gap-3">
                    <h2 class="text-ink-900 min-w-0 truncate text-[14px] leading-tight font-semibold tracking-tight">
                      {{ section.title }}
                    </h2>
                  </div>
                  <ChevronDown
                    v-if="section.collapsible"
                    class="text-ink-400 h-4 w-4 shrink-0 transition-transform duration-150"
                    :class="collapsed[section.key] ? `-rotate-90 rtl:rotate-90` : `rotate-0`"
                  />
                </header>
                <div v-if="!collapsed[section.key]" class="p-5">
                  <div class="grid w-full grid-cols-1 gap-x-5 gap-y-5 sm:grid-cols-2 lg:grid-cols-3">
                    <DarejerComponent
                      v-for="component in componentsForSection(section)"
                      :key="component.name"
                      :component="component"
                      :record="record"
                      :errors="mergedErrors"
                      :form-data="formData"
                      @update="handleFieldUpdate"
                      @prefill="handlePrefill"
                    />
                  </div>
                </div>
              </section>
            </template>

            <!-- Fallback: single FastTab -->
            <template v-if="!hasTabs && !hasSections">
              <section
                class="border-paper-200 bg-card relative overflow-hidden rounded-md border shadow-[0_1px_0_rgba(0,0,0,0.02)]"
                :class="isReport ? 'print:hidden' : ''"
              >
                <header
                  class="flex cursor-pointer items-center justify-between gap-3 px-5 py-3 transition-colors select-none"
                  :class="
                    !collapsed['General']
                      ? `border-paper-200 from-paper-75 to-card border-b bg-linear-to-b`
                      : 'hover:bg-paper-75'
                  "
                  role="banner"
                  @click="toggleSection('General')"
                >
                  <div class="flex min-w-0 items-center gap-3">
                    <h2 class="text-ink-900 text-[14px] leading-tight font-semibold tracking-tight">
                      {{ __('General') }}
                    </h2>
                  </div>
                  <ChevronDown
                    class="text-ink-400 h-4 w-4 transition-transform duration-150"
                    :class="collapsed['General'] ? `-rotate-90 rtl:rotate-90` : `rotate-0`"
                  />
                </header>
                <div v-if="!collapsed['General']" class="p-5">
                  <div class="grid w-full grid-cols-1 gap-x-5 gap-y-5 sm:grid-cols-2 lg:grid-cols-3">
                    <DarejerComponent
                      v-for="component in components"
                      :key="component.name"
                      :component="component"
                      :record="record"
                      :errors="mergedErrors"
                      :form-data="formData"
                      @update="handleFieldUpdate"
                      @prefill="handlePrefill"
                    />
                  </div>
                </div>
              </section>
            </template>

            <!-- Report results — shown when controller passed `rows` -->
            <ReportResults
              v-if="isReport && reportRows"
              :rows="reportRows"
              :totals="reportTotals"
              :columns="reportColumns"
            />
          </div>
        </div>
      </div>
    </div>
  </template>

  <!-- ── Dialog mode ─────────────────────────────────────────────── -->
  <template v-else>
    <Dialog :open="true">
      <DialogContent
        class="flex max-h-[calc(100dvh-2rem)] w-[calc(100vw-2rem)] flex-col overflow-hidden rounded-md p-0 shadow-[0_24px_64px_-12px_rgba(0,0,0,0.25)]"
        :class="dialogSizeClass[dialogSize ?? 'md'] ?? 'sm:max-w-lg'"
      >
        <DialogHeader
          class="border-paper-200 from-paper-75 to-card relative shrink-0 border-b bg-linear-to-b px-5 py-4"
        >
          <span class="bg-linear-to-e from-brand-500 via-brand-400 absolute inset-x-0 top-0 h-0.5 to-transparent" />
          <DialogTitle class="text-ink-900 text-[15px] font-semibold tracking-tight">{{ title }}</DialogTitle>
        </DialogHeader>

        <div class="scrollbar-darejer bg-card min-h-0 flex-1 scrollbar-gutter-stable overflow-y-auto px-5 py-5">
          <div class="grid w-full grid-cols-1 gap-x-5 gap-y-4 sm:grid-cols-2">
            <DarejerComponent
              v-for="component in components"
              :key="component.name"
              :component="component"
              :record="record"
              :errors="mergedErrors"
              :form-data="formData"
              @update="handleFieldUpdate"
              @prefill="handlePrefill"
            />
          </div>
        </div>

        <div class="border-paper-200 bg-paper-75 flex shrink-0 justify-end gap-2 border-t px-5 py-3">
          <DarejerActions
            :actions="actions"
            placement="dialog"
            :form-data="formData"
            :processing="processing"
            :is-dirty="isDirty"
            :on-save="submit"
            :on-cancel="cancel"
          />
        </div>
      </DialogContent>
    </Dialog>
  </template>
</template>
