<script setup lang="ts">
import { computed, ref, watch, shallowRef } from 'vue'
import { router } from '@inertiajs/vue3'
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
}                          from '@/components/ui/dialog'
import DarejerComponent    from '@/components/darejer/DarejerComponent.vue'
import DarejerActions      from '@/components/darejer/DarejerActions.vue'
import useTranslation      from '@/composables/useTranslation'
import type {
    DarejerComponent as DarejerComponentType,
    DarejerAction,
}                          from '@/types/darejer'

/**
 * Hosts a form *inline* inside a Dialog — used when a `ModalToggleAction`
 * carries a `form` schema. Submits via Inertia's router so a successful
 * response (redirect, flash, etc.) drives the page transition naturally.
 *
 * Schema shape comes from `Darejer\Forms\Form::toArray()` —
 * `{ title, components, actions, record }`. Any darejer component is a
 * valid input; rendering goes through DarejerComponent so the same
 * widgets, validation, and dependOn rules used by full-page Screens apply.
 */

const { __ } = useTranslation()

interface ModalForm {
    title:      string
    components: DarejerComponentType[]
    actions:    DarejerAction[]
    record:     Record<string, unknown>
}

const props = defineProps<{
    open:      boolean
    form:      ModalForm
    modalSize?: string
}>()

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void
}>()

const formData  = ref<Record<string, unknown>>({})
const errors    = ref<Record<string, string>>({})
const processing = shallowRef(false)
const isDirty   = shallowRef(false)

const saveAction = computed<DarejerAction | undefined>(() =>
    props.form.actions.find(a => a.type === 'Save'),
)

// Drop Save/Cancel from the rendered footer toolbar — DarejerActions wires
// them through onSave/onCancel, and we want the dialog buttons (not the
// raw action buttons) to drive submit/close.
const footerActions = computed<DarejerAction[]>(() =>
    props.form.actions.filter(a => a.type !== 'Delete'),
)

function buildInitial(): Record<string, unknown> {
    const data: Record<string, unknown> = {}
    for (const c of props.form.components) {
        data[c.name] = props.form.record?.[c.name] ?? c.default ?? null
    }
    return data
}

watch(() => props.open, (open) => {
    if (open) {
        formData.value = buildInitial()
        errors.value   = {}
        isDirty.value  = false
    }
}, { immediate: true })

function updateField(name: string, value: unknown) {
    formData.value[name] = value
    isDirty.value = true
}

function submit() {
    const url = (saveAction.value?.url as string) ?? ''
    const method = ((saveAction.value?.method ?? 'POST').toLowerCase()) as
        'post' | 'put' | 'patch' | 'delete'
    if (!url) return

    processing.value = true
    errors.value = {}

    const options = {
        preserveScroll: true,
        preserveState: false,
        // 422 validation responses route here. Surface errors to the form
        // and keep the dialog open so the user can correct and retry.
        onError: (e: Record<string, string>) => {
            errors.value = e
        },
        // 200/302 responses route here. Inertia routes `back()->withErrors`
        // through onSuccess (the redirect itself succeeded, even though
        // it carried errors) — so check page.props.errors before closing.
        onSuccess: (page: { props?: { errors?: Record<string, string> } }) => {
            const pageErrors = page?.props?.errors ?? {}
            if (Object.keys(pageErrors).length > 0) {
                errors.value = pageErrors
                return
            }
            emit('update:open', false)
            // Inertia v3 can short-circuit the follow-up GET when the
            // server redirects back to the current URL — the dialog
            // closes but the host page still shows stale data until a
            // manual browser refresh. Force a fresh visit so the host
            // page picks up the updated record/props.
            router.visit(window.location.pathname + window.location.search, {
                preserveScroll: true,
                preserveState: false,
                replace: true,
            })
        },
        onFinish: () => {
            processing.value = false
        },
    }

    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    router[method](url, formData.value as any, options)
}

function cancel() {
    emit('update:open', false)
}

const dialogSizeClass: Record<string, string> = {
    xs:   'sm:max-w-xs',
    sm:   'sm:max-w-md',
    md:   'sm:max-w-lg',
    lg:   'sm:max-w-3xl',
    xl:   'sm:max-w-5xl',
    full: 'sm:max-w-[95vw]',
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent
            class="p-0 flex flex-col w-[calc(100vw-2rem)] max-h-[calc(100dvh-2rem)] overflow-hidden rounded-md shadow-[0_24px_64px_-12px_rgba(0,0,0,0.25)]"
            :class="dialogSizeClass[modalSize ?? 'md'] ?? 'sm:max-w-lg'"
        >
            <DialogHeader class="relative shrink-0 px-5 py-4 border-b border-paper-200 bg-gradient-to-b from-paper-75 to-card">
                <span class="absolute inset-x-0 top-0 h-0.5 bg-gradient-to-r from-brand-500 via-brand-400 to-transparent" />
                <DialogTitle class="text-[15px] font-semibold text-ink-900 tracking-tight">
                    {{ __(form.title) }}
                </DialogTitle>
            </DialogHeader>

            <div class="flex-1 min-h-0 overflow-y-auto px-5 py-5 bg-card">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-5 gap-y-4 w-full">
                    <DarejerComponent
                        v-for="component in form.components"
                        :key="component.name"
                        :component="component"
                        :record="form.record"
                        :errors="errors"
                        :form-data="formData"
                        @update="updateField"
                    />
                </div>
            </div>

            <div class="shrink-0 flex justify-end gap-2 px-5 py-3 border-t border-paper-200 bg-paper-75">
                <DarejerActions
                    :actions="footerActions"
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
