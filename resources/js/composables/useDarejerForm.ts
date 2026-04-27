import { shallowReactive, shallowRef, computed } from 'vue'
import { useForm, router }    from '@inertiajs/vue3'
import type { DarejerComponent } from '@/types/darejer'

export interface DarejerFormOptions {
    url:         string
    method:      'post' | 'put' | 'patch' | 'delete'
    components:  DarejerComponent[]
    record:      Record<string, unknown>
    onSuccess?:  (response?: unknown) => void
    onError?:    (errors: Record<string, string>) => void
    dialog?:     boolean
}

// Inertia's useForm constrains field values. We feed anything through using
// `unknown` at the call-site via casts — the shape is validated server-side.
type FormFieldValue = string | number | boolean | File | File[] | null

/**
 * Darejer form composable.
 *
 * Collects every component's value, handles translatable fields and file
 * uploads, submits via Inertia v3 `useForm`, and propagates validation
 * errors back to each component's FieldWrapper. File fields automatically
 * trigger `forceFormData: true` so submissions become multipart.
 *
 * Reactivity note: `formData` is `shallowReactive` (not deep). Nested
 * translatable bags / repeater rows are stored as plain objects — we never
 * mutate them through `formData`, the leaf components own their own
 * deep-reactive state and emit whole replacement snapshots. This keeps
 * per-keystroke cost at O(1) reactivity triggers instead of O(nested).
 */
export function useDarejerForm(options: DarejerFormOptions) {

    function buildInitialData(): Record<string, unknown> {
        const data: Record<string, unknown> = {}

        for (const component of options.components) {
            const name  = component.name
            const value = options.record[name] ?? component.default ?? null
            data[name]  = value
        }

        return data
    }

    const initial = buildInitialData()

    // useForm is used only for submission — NOT for tracking per-keystroke
    // state. Writing into it on every keystroke would burn its own dirty-
    // diffing logic for no benefit.
    const form = useForm(initial as Record<string, FormFieldValue>)

    // Shallow-reactive so `formData[name] = value` triggers per-key updates
    // but values themselves are plain and not proxied. Children diff-track
    // only the specific key(s) they depend on.
    const formData = shallowReactive<Record<string, unknown>>({ ...initial })

    // Dirty state as a single boolean ref — flips to true on the first real
    // mutation. Avoids re-iterating every field's keys per keystroke.
    const isDirty = shallowRef(false)

    function updateField(name: string, value: unknown) {
        formData[name] = value as FormFieldValue

        if (!isDirty.value && value !== initial[name]) {
            isDirty.value = true
        }
    }

    const errors = computed((): Record<string, string> =>
        ({ ...form.errors } as Record<string, string>)
    )

    function hasAnyFile(value: unknown): boolean {
        if (value instanceof File) return true
        if (Array.isArray(value)) return value.some(hasAnyFile)
        return false
    }

    function submit() {
        // Sync reactive formData back into useForm before dispatching.
        for (const [key, value] of Object.entries(formData)) {
            ;(form as unknown as Record<string, unknown>)[key] = value
        }

        const hasFiles = Object.values(formData).some(hasAnyFile)

        const submitOptions = {
            preserveScroll: true,
            forceFormData:  hasFiles,
            onSuccess: (page?: { url?: string; props?: Record<string, unknown> }) => {
                isDirty.value = false
                if (options.dialog && typeof window !== 'undefined') {
                    // Broadcast so the parent screen (e.g. a Combobox that opened
                    // this dialog) can react — typically by refreshing its data
                    // and auto-selecting the newly created record.
                    window.dispatchEvent(new CustomEvent('darejer:dialog-saved', {
                        detail: {
                            url: page?.url ?? null,
                            flash: (page?.props as { flash?: unknown } | undefined)?.flash ?? null,
                        },
                    }))
                    window.history.back()
                }
                options.onSuccess?.(page)
            },
            onError: (errs: Record<string, string>) => {
                options.onError?.(errs)
            },
        }

        form.submit(options.method, options.url, submitOptions)
    }

    function reset() {
        for (const key of Object.keys(formData)) delete formData[key]
        Object.assign(formData, buildInitialData())
        form.reset()
        isDirty.value = false
    }

    function cancel(cancelUrl?: string) {
        reset()

        if (options.dialog && typeof window !== 'undefined') {
            window.history.back()
            return
        }

        if (cancelUrl) {
            router.visit(cancelUrl)
        }
    }

    return {
        form,
        formData,
        errors,
        processing: computed(() => form.processing),
        isDirty,
        updateField,
        submit,
        reset,
        cancel,
    }
}
