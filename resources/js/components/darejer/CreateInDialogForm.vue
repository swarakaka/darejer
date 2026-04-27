<script setup lang="ts">
import { computed, shallowRef } from 'vue'
import { useHttp }         from '@inertiajs/vue3'
import DarejerComponent    from '@/components/darejer/DarejerComponent.vue'
import DarejerActions      from '@/components/darejer/DarejerActions.vue'
import type {
    DarejerComponent as DarejerComponentType,
    DarejerAction,
}                          from '@/types/darejer'

const props = defineProps<{
    title:      string
    components: DarejerComponentType[]
    actions:    DarejerAction[]
    record:     Record<string, unknown>
}>()

const emit = defineEmits<{
    (e: 'cancel'):  void
    (e: 'created', payload: { url: string | null; flash: unknown }): void
}>()

// Drop Delete from the inline create dialog — record doesn't exist yet.
const visibleActions = computed<DarejerAction[]>(() =>
    props.actions.filter(a => a.type !== 'Delete')
)

const saveAction = computed<DarejerAction | undefined>(() =>
    props.actions.find(a => a.type === 'Save')
)

// Build initial form data from components × record. The shape is fully
// dynamic, so we don't try to type the bag — useHttp handles the runtime
// serialization.
function buildInitial(): Record<string, unknown> {
    const data: Record<string, unknown> = {}
    for (const c of props.components) {
        data[c.name] = props.record[c.name] ?? c.default ?? null
    }
    return data
}

// useHttp tracks form fields, processing state, validation errors, and
// dirtiness in one bag. We mutate fields directly (http[name] = value).
// useHttp is itself an Inertia v3 hook — it sets Inertia's headers/CSRF
// internally, so the response is already the page JSON shape.
// eslint-disable-next-line @typescript-eslint/no-explicit-any
const http = useHttp(buildInitial() as any) as unknown as Record<string, unknown> & {
    errors: Record<string, string>
    processing: boolean
    get(url: string, opts?: object): void
    post(url: string, opts?: object): void
    put(url: string, opts?: object): void
    patch(url: string, opts?: object): void
    delete(url: string, opts?: object): void
}

// Mirror of useHttp's error map for DarejerComponent's prop shape.
const errors = computed<Record<string, string>>(() => http.errors ?? {})

const isDirty = shallowRef(false)

function updateField(name: string, value: unknown) {
    http[name] = value
    isDirty.value = true
}

type InertiaPageJson = {
    url?:   string
    props?: Record<string, unknown>
}

function submit() {
    const url = (saveAction.value?.url as string) ?? ''
    const method = (saveAction.value?.method?.toLowerCase() ?? 'post') as
        'post' | 'put' | 'patch' | 'delete'
    if (!url) return

    http[method](url, {
        onSuccess: (response: InertiaPageJson) => {
            isDirty.value = false
            emit('created', {
                url:   response?.url ?? null,
                flash: (response?.props as { flash?: unknown } | undefined)?.flash ?? null,
            })
        },
    })
}
</script>

<template>
    <div class="flex-1 min-h-0 overflow-y-auto px-5 py-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-5 gap-y-3.5 w-full">
            <DarejerComponent
                v-for="component in components"
                :key="component.name"
                :component="component"
                :record="record"
                :errors="errors"
                :form-data="(http as unknown as Record<string, unknown>)"
                @update="updateField"
            />
        </div>
    </div>

    <div class="shrink-0 flex justify-end gap-2 px-5 py-3 border-t border-paper-200 bg-paper-75">
        <DarejerActions
            :actions="visibleActions"
            placement="dialog"
            :form-data="(http as unknown as Record<string, unknown>)"
            :processing="http.processing"
            :is-dirty="isDirty"
            :on-save="submit"
            :on-cancel="() => emit('cancel')"
        />
    </div>
</template>
