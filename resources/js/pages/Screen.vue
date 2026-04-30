<script setup lang="ts">
import { ref, computed } from 'vue'
import AppLayout            from '@/layouts/AppLayout.vue'
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
}                           from '@/components/ui/dialog'
import { ChevronDown, Circle } from 'lucide-vue-next'
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
        (props.sections ?? []).map(s => [s.title, s.alwaysExpanded ? false : (s.collapsed ?? false)])
    )
)

function toggleSection(title: string) {
    const section = (props.sections ?? []).find(s => s.title === title)
    if (section?.alwaysExpanded) {
        return
    }
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

const dialogSizeClass: Record<string, string> = {
    xs:   'sm:max-w-xs',
    sm:   'sm:max-w-md',
    md:   'sm:max-w-lg',
    lg:   'sm:max-w-3xl',
    xl:   'sm:max-w-5xl',
    full: 'sm:max-w-[95vw]',
}

const visibleSections = computed(() =>
    (props.sections ?? []).filter(isSectionVisible)
)

function sectionIndex(section: ScreenSection): number {
    return visibleSections.value.findIndex(s => s.title === section.title) + 1
}
</script>

<template>

    <!-- ── Full-page mode ──────────────────────────────────────────── -->
    <template v-if="!isDialog">
        <div class="flex flex-col h-full overflow-hidden bg-paper-100">

            <!-- Action Pane -->
            <div class="flex items-center gap-1.5 h-(--action-pane-height) px-6 border-b border-paper-200 bg-white shrink-0 overflow-x-auto">
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
                    class="ms-auto inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-warning-50 ring-1 ring-inset ring-warning-100 text-[10px] font-bold uppercase tracking-[0.14em] text-warning-700 shadow-[0_1px_0_rgba(0,0,0,0.02)]"
                >
                    <span class="relative flex w-1.5 h-1.5">
                        <span class="absolute inset-0 rounded-full bg-warning-500 animate-ping opacity-75" />
                        <span class="relative w-1.5 h-1.5 rounded-full bg-warning-500" />
                    </span>
                    {{ __('Unsaved changes') }}
                </span>
                <span
                    v-else-if="processing"
                    class="ms-auto inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-brand-50 ring-1 ring-inset ring-brand-100 text-[10px] font-bold uppercase tracking-[0.14em] text-brand-700"
                >
                    <Circle class="w-2.5 h-2.5 animate-spin" />
                    {{ __('Saving') }}
                </span>
            </div>

            <!-- Scrolling content -->
            <div :class="['flex-1 overflow-y-auto', fullWidth ? 'flex flex-col' : '']">

                <!-- Page title — hero with subtle gradient -->
                <header class="shrink-0 relative bg-white border-b border-paper-200 overflow-hidden">
                    <div
                        class="absolute inset-0 opacity-[0.35] pointer-events-none"
                        style="
                            background-image: radial-gradient(circle at 1px 1px, var(--color-paper-200) 1px, transparent 0);
                            background-size: 20px 20px;
                        "
                    />
                    <div class="absolute inset-y-0 inset-e-0 w-2/3 bg-linear-to-s from-brand-50/60 via-white/0 to-transparent pointer-events-none" />

                    <div class="relative flex items-start justify-between gap-6 px-6 pt-5 pb-5">
                        <div class="flex flex-col min-w-0">
                            <AppBreadcrumbs class="mb-2" />
                            <h1 class="text-[28px] leading-[1.05] tracking-[-0.02em] text-ink-900 font-semibold">
                                {{ title }}
                            </h1>
                        </div>
                    </div>
                </header>

                <!-- Body content -->
                <div :class="['flex-1', fullWidth ? 'flex flex-col min-h-0' : 'px-6 pt-5 pb-6']">

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

                    <!-- Horizontal tabs / FastTabs -->
                    <div v-else class="space-y-4">
                        <template v-if="hasTabs">
                            <Tabs :default-value="defaultTabValue" class="w-full">
                                <TabsList class="h-auto w-full justify-start gap-0 rounded-md bg-white p-1 mb-4 overflow-x-auto border border-paper-200 shadow-[0_1px_0_rgba(0,0,0,0.02)]">
                                    <TabsTrigger
                                        v-for="tab in visibleTabs"
                                        :key="tab.title"
                                        :value="tab.title"
                                        class="text-[13px] font-semibold tracking-tight text-ink-600 data-[state=active]:text-brand-700 data-[state=active]:bg-brand-50 data-[state=active]:shadow-[inset_0_-2px_0_var(--color-brand-500)] data-[state=active]:rounded-[2px] hover:text-ink-900 transition-colors px-4 py-2"
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
                                    <section class="relative bg-white border border-paper-200 rounded-md overflow-hidden p-5 shadow-[0_1px_0_rgba(0,0,0,0.02)]">
                                        <span class="absolute inset-x-0 top-0 h-0.5 bg-gradient-to-r from-brand-500 via-brand-400 to-transparent" />
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

                        <!-- FastTab sections — numbered, refined headers -->
                        <template v-if="hasSections">
                            <section
                                v-for="section in (sections ?? [])"
                                v-show="isSectionVisible(section)"
                                :key="section.title"
                                class="group/section relative bg-white border border-paper-200 rounded-md overflow-hidden
                                       shadow-[0_1px_0_rgba(0,0,0,0.02)] transition-all duration-150"
                                :class="!collapsed[section.title]
                                          ? 'ring-1 ring-brand-100/50 border-paper-300'
                                          : 'hover:border-paper-300'"
                            >
                                <!-- Accent rail when expanded -->
                                <span
                                    v-if="!collapsed[section.title]"
                                    class="absolute inset-y-0 start-0 w-0.5 bg-gradient-to-b from-brand-500 via-brand-400 to-brand-500/30"
                                />

                                <header
                                    class="flex items-center justify-between gap-3 px-5 py-3 select-none transition-colors"
                                    :class="[
                                        !collapsed[section.title]
                                            ? 'border-b border-paper-200 bg-gradient-to-b from-paper-75 to-white'
                                            : 'bg-white',
                                        section.alwaysExpanded ? '' : 'cursor-pointer hover:bg-paper-75',
                                    ]"
                                    :role="section.alwaysExpanded ? undefined : 'button'"
                                    :aria-expanded="section.alwaysExpanded ? undefined : !collapsed[section.title]"
                                    @click="toggleSection(section.title)"
                                >
                                    <div class="flex items-center gap-3 min-w-0">
                                        <h2 class="text-[14px] font-semibold tracking-tight leading-tight text-ink-900 truncate min-w-0">
                                            {{ section.title }}
                                        </h2>
                                    </div>
                                    <ChevronDown
                                        v-if="!section.alwaysExpanded"
                                        class="w-4 h-4 text-ink-400 transition-transform duration-150 shrink-0"
                                        :class="collapsed[section.title] ? '-rotate-90 rtl:rotate-90' : 'rotate-0'"
                                    />
                                </header>
                                <div
                                    v-if="!collapsed[section.title]"
                                    class="p-5"
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
                            <section class="relative bg-white border border-paper-200 rounded-md overflow-hidden shadow-[0_1px_0_rgba(0,0,0,0.02)]">
                                <span
                                    v-if="!collapsed['General']"
                                    class="absolute inset-y-0 start-0 w-0.5 bg-gradient-to-b from-brand-500 via-brand-400 to-brand-500/30"
                                />
                                <header
                                    class="flex items-center justify-between gap-3 px-5 py-3 cursor-pointer select-none transition-colors"
                                    :class="!collapsed['General']
                                              ? 'border-b border-paper-200 bg-linear-to-b from-paper-75 to-white'
                                              : 'hover:bg-paper-75'"
                                    role="banner"
                                    @click="toggleSection('General')"
                                >
                                    <div class="flex items-center gap-3 min-w-0">
                                        <h2 class="text-[14px] font-semibold tracking-tight leading-tight text-ink-900">{{ __('General') }}</h2>
                                    </div>
                                    <ChevronDown
                                        class="w-4 h-4 text-ink-400 transition-transform duration-150"
                                        :class="collapsed['General'] ? '-rotate-90 rtl:rotate-90' : 'rotate-0'"
                                    />
                                </header>
                                <div
                                    v-if="!collapsed['General']"
                                    class="p-5"
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
        </div>
    </template>

    <!-- ── Dialog mode ─────────────────────────────────────────────── -->
    <template v-else>
        <Dialog :open="true">
            <DialogContent
                class="p-0 flex flex-col w-[calc(100vw-2rem)] max-h-[calc(100dvh-2rem)] overflow-hidden rounded-md shadow-[0_24px_64px_-12px_rgba(0,0,0,0.25)]"
                :class="dialogSizeClass[dialogSize ?? 'md'] ?? 'sm:max-w-lg'"
            >
                <DialogHeader class="relative shrink-0 px-5 py-4 border-b border-paper-200 bg-gradient-to-b from-paper-75 to-white">
                    <span class="absolute inset-x-0 top-0 h-0.5 bg-gradient-to-r from-brand-500 via-brand-400 to-transparent" />
                    <DialogTitle class="text-[15px] font-semibold text-ink-900 tracking-tight">{{ title }}</DialogTitle>
                </DialogHeader>

                <div class="flex-1 min-h-0 overflow-y-auto px-5 py-5 bg-white">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-5 gap-y-4 w-full">
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
