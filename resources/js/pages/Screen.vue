<script setup lang="ts">
import { ref, computed } from 'vue'
import AppLayout            from '@/layouts/AppLayout.vue'
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
}                           from '@/components/ui/dialog'
import { ChevronDown } from 'lucide-vue-next'
import DarejerComponent     from '@/components/darejer/DarejerComponent.vue'
import DarejerActions       from '@/components/darejer/DarejerActions.vue'
import AppBreadcrumbs       from '@/components/darejer/AppBreadcrumbs.vue'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { useDarejerForm }   from '@/composables/useDarejerForm'
import { evaluateDependOn } from '@/composables/useDependOn'
import useTranslation       from '@/composables/useTranslation'
import type { ScreenProps, ScreenSection, ScreenTab, DarejerComponent as DarejerComponentType } from '@/types/darejer'

defineOptions({ layout: AppLayout })

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
const saveAction = computed(() =>
    props.actions.find(a => a.type === 'Save'),
)

// Darejer form — wraps Inertia useForm, collects component values,
// handles files + translatable fields, surfaces errors into FieldWrapper.
const {
    formData,
    errors: formErrors,
    processing,
    isDirty,
    updateField,
    submit,
    cancel,
} = useDarejerForm({
    url:        (saveAction.value?.url as string) ?? '',
    method:     ((saveAction.value?.method?.toLowerCase() ?? 'post') as 'post' | 'put' | 'patch' | 'delete'),
    components: props.components ?? [],
    record:     (props.record ?? {}) as Record<string, unknown>,
    dialog:     isDialog.value,
})

// Merge server-sent errors (Inertia shared props) with useForm's own errors.
const mergedErrors = computed<Record<string, string>>(() => ({
    ...(props.errors ?? {}),
    ...formErrors.value,
}))

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
    for (const s of (props.sections ?? [])) {
        collect(s.dependOn)
    }
    for (const t of (props.tabs ?? [])) {
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
    Object.fromEntries(
        (props.sections ?? []).map(s => [s.title, s.collapsed ?? false])
    )
)

function toggleSection(title: string) {
    collapsed.value[title] = !collapsed.value[title]
}

const hasSections = computed(() =>
    !!(props.sections && props.sections.length > 0)
)

const hasTabs = computed(() =>
    !!(props.tabs && props.tabs.length > 0)
)

const visibleTabs = computed<ScreenTab[]>(() =>
    (props.tabs ?? []).filter(isTabVisible)
)

const defaultTabValue = computed<string | undefined>(() =>
    visibleTabs.value[0]?.title
)

function componentsForSection(section: ScreenSection) {
    return props.components.filter(c =>
        section.components.includes(c.name)
    )
}

function componentsForTab(tab: ScreenTab) {
    return props.components.filter(c =>
        tab.components.includes(c.name)
    )
}

const dialogSizeMap: Record<string, string> = {
    xs:   '20rem',
    sm:   '28rem',
    md:   '36rem',
    lg:   '48rem',
    xl:   '60rem',
    full: '95vw',
}
</script>

<template>

    <!-- ── Full-page mode ──────────────────────────────────────────── -->
    <template v-if="!isDialog">
        <div class="flex flex-col h-full overflow-hidden bg-paper-50">

            <!-- Action Pane -->
            <div
                class="flex items-center gap-1.5 px-6 border-b border-paper-200 bg-paper-75 shrink-0 overflow-x-auto"
                :style="{ height: 'var(--action-pane-height)' }"
            >
                <DarejerActions
                    :actions="actions"
                    placement="header"
                    :form-data="formData"
                    :processing="processing"
                    :is-dirty="isDirty"
                    :on-save="submit"
                    :on-cancel="cancel"
                />
                <span
                    v-if="isDirty && !processing"
                    class="ms-auto text-[11px] font-medium uppercase tracking-[0.12em] text-warning-600"
                >
                    {{ __('Unsaved changes') }}
                </span>
            </div>

            <!-- Scrolling content -->
            <div :class="['flex-1 overflow-y-auto', fullWidth ? 'px-0 pb-0 flex flex-col' : 'px-6 pb-6']">

                <!-- Page title -->
                <div :class="['flex items-start justify-between gap-6 pt-5 pb-4 border-b border-paper-200 mb-5', fullWidth ? 'px-6' : '']">
                    <div class="flex flex-col min-w-0">
                        <AppBreadcrumbs class="mb-3" />
                        <h1 class="text-[1.75rem] leading-[1.1] tracking-tight text-ink-900">
                            {{ title }}
                        </h1>
                    </div>
                </div>

                <!-- Full-width mode: no FastTab wrapper -->
                <template v-if="fullWidth">
                    <div class="flex-1 flex flex-col min-h-0">
                        <DarejerComponent
                            v-for="component in components"
                            :key="component.name"
                            :component="component"
                            :record="record"
                            :errors="mergedErrors"
                            :form-data="formData"
                            class="flex-1 min-h-0"
                            @update="handleFieldUpdate"
                            @prefill="handlePrefill"
                        />
                    </div>
                </template>

                <!-- Horizontal tabs -->
                <div v-else class="space-y-6">
                    <template v-if="hasTabs">
                        <Tabs :default-value="defaultTabValue" class="w-full">
                            <TabsList class="h-auto w-full justify-start gap-1 rounded-md bg-paper-100 p-1 mb-4 overflow-x-auto">
                                <TabsTrigger
                                    v-for="tab in visibleTabs"
                                    :key="tab.title"
                                    :value="tab.title"
                                    class="text-sm tracking-tight text-ink-700"
                                >
                                    {{ tab.title }}
                                </TabsTrigger>
                            </TabsList>
                            <TabsContent
                                v-for="tab in visibleTabs"
                                :key="tab.title"
                                :value="tab.title"
                                class="mt-0"
                            >
                                <section class="border border-paper-200 rounded-md overflow-hidden bg-white p-4">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-5 gap-y-5 w-full">
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

                    <!-- FastTab sections -->
                    <template v-if="hasSections">
                    <section
                        v-for="section in (sections ?? [])"
                        v-show="isSectionVisible(section)"
                        :key="section.title"
                        class="border border-paper-200 rounded-md overflow-hidden bg-white"
                    >
                        <header
                            class="flex items-center justify-between gap-3 px-4 py-2.5 cursor-pointer select-none hover:bg-paper-50 transition-colors"
                            :class="!collapsed[section.title] ? 'border-b border-paper-200' : ''"
                            role="button"
                            :aria-expanded="!collapsed[section.title]"
                            @click="toggleSection(section.title)"
                        >
                            <h2 class="text-lg tracking-tight leading-[1.15] text-ink-700 truncate min-w-0">
                                {{ section.title }}
                            </h2>
                            <ChevronDown
                                class="w-4 h-4 text-ink-400 transition-transform duration-150 shrink-0"
                                :class="collapsed[section.title] ? '-rotate-90' : 'rotate-0'"
                            />
                        </header>
                        <div
                            v-if="!collapsed[section.title]"
                            class="p-4"
                        >
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-5 gap-y-5 w-full">
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
                        <section class="border border-paper-200 rounded-md overflow-hidden bg-white">
                            <header
                                class="flex items-center justify-between gap-3 px-4 py-2.5 cursor-pointer select-none hover:bg-paper-50 transition-colors"
                                :class="!collapsed['General'] ? 'border-b border-paper-200' : ''"
                                role="button"
                                @click="toggleSection('General')"
                            >
                                <h2 class="text-lg tracking-tight leading-[1.15] text-ink-700">{{ __('General') }}</h2>
                                <ChevronDown
                                    class="w-4 h-4 text-ink-400 transition-transform duration-150"
                                    :class="collapsed['General'] ? '-rotate-90' : 'rotate-0'"
                                />
                            </header>
                            <div
                                v-if="!collapsed['General']"
                                class="p-4"
                            >
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-5 gap-y-5 w-full">
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
                </div>

            </div>
        </div>
    </template>

    <!-- ── Dialog mode ─────────────────────────────────────────────── -->
    <template v-else>
        <Dialog :open="true">
            <DialogContent
                class="p-0 flex flex-col w-[calc(100vw-2rem)] max-h-[calc(100dvh-2rem)] overflow-hidden"
                :style="{ maxWidth: dialogSizeMap[dialogSize ?? 'md'] ?? '36rem' }"
            >
                <DialogHeader class="shrink-0 px-5 py-4 border-b border-paper-200 bg-paper-75">
                    <DialogTitle class="text-xl">{{ title }}</DialogTitle>
                </DialogHeader>

                <div class="flex-1 min-h-0 overflow-y-auto px-5 py-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-5 gap-y-3.5 w-full">
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

                <div class="shrink-0 flex justify-end gap-2 px-5 py-3 border-t border-paper-200 bg-paper-75">
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
