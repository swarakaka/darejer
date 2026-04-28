<script setup lang="ts">
import { ref, watch, shallowRef } from 'vue'
import { useHttp }          from '@inertiajs/vue3'
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
}                           from '@/components/ui/dialog'
import { Loader2 }          from 'lucide-vue-next'
import CreateInDialogForm   from '@/components/darejer/CreateInDialogForm.vue'
import useTranslation       from '@/composables/useTranslation'
import type {
    DarejerComponent as DarejerComponentType,
    DarejerAction,
}                           from '@/types/darejer'

const { __ } = useTranslation()

const props = withDefaults(defineProps<{
    open: boolean
    url:  string
    /**
     * 'form' = a reusable Form endpoint exposed via DarejerController::forms().
     *          The response is plain JSON `{ title, components, actions, record }`.
     * 'page' = a full create-page route. The response is an Inertia page JSON,
     *          so the schema is nested under `.props`.
     */
    mode?: 'form' | 'page'
}>(), {
    mode: 'page',
})

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void
    (e: 'created', payload: { url: string | null; flash: unknown }): void
}>()

type FetchedPage = {
    title:      string
    components: DarejerComponentType[]
    actions:    DarejerAction[]
    record:     Record<string, unknown>
}

type InertiaPageJson = {
    component?: string
    url?:       string
    version?:   string | null
    props?:     Record<string, unknown>
}

const error   = ref<string | null>(null)
const fetched = shallowRef<FetchedPage | null>(null)

// Strip ?_dialog=1 — only relevant for legacy 'page' mode where the Combobox
// appended it for the navigation flow. We render in our own Dialog and don't
// want the fetched Screen to mark itself as inside one.
function cleanUrl(raw: string): string {
    try {
        const u = new URL(raw, window.location.origin)
        u.searchParams.delete('_dialog')
        return u.pathname + (u.search ? u.search : '') + u.hash
    } catch {
        return raw
    }
}

const http = useHttp<Record<string, never>, InertiaPageJson>()

function fetchPage() {
    error.value = null
    fetched.value = null

    http.get(cleanUrl(props.url), {
        onSuccess: (response) => {
            // 'form' mode returns the schema directly; 'page' mode returns
            // an Inertia page JSON with the schema nested under `.props`.
            const raw = (response ?? {}) as Record<string, unknown>
            const p = (props.mode === 'form'
                ? raw
                : ((raw.props as Record<string, unknown> | undefined) ?? {})) as Record<string, unknown>
            fetched.value = {
                title:      (p.title as string) ?? 'New',
                components: (p.components as DarejerComponentType[]) ?? [],
                actions:    (p.actions as DarejerAction[]) ?? [],
                record:     (p.record as Record<string, unknown>) ?? {},
            }
        },
        onHttpException: (httpResponse) => {
            error.value = __('Failed to load form (HTTP :status).', { status: httpResponse.status })
        },
        onNetworkError: () => {
            error.value = __('Network error while loading the form.')
        },
    })
}

watch(() => props.open, (open) => {
    if (open) {
        fetchPage()
    } else {
        fetched.value = null
        error.value = null
    }
})

function onCancel() {
    emit('update:open', false)
}

function onCreated(payload: { url: string | null; flash: unknown }) {
    emit('created', payload)
    emit('update:open', false)
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent
            class="p-0 flex flex-col w-[calc(100vw-2rem)] max-h-[calc(100dvh-2rem)] overflow-hidden"
            style="max-width: 36rem"
        >
            <DialogHeader class="shrink-0 px-5 py-4 border-b border-paper-200 bg-paper-75">
                <DialogTitle class="text-xl">
                    {{ fetched?.title ?? __('Loading…') }}
                </DialogTitle>
            </DialogHeader>

            <div v-if="http.processing" class="flex-1 flex items-center justify-center py-10 text-ink-400 text-sm gap-2">
                <Loader2 class="w-4 h-4 animate-spin" /> {{ __('Loading form…') }}
            </div>

            <div v-else-if="error" class="flex-1 flex items-center justify-center py-10 text-danger-600 text-sm">
                {{ error }}
            </div>

            <!-- Render the form in a CHILD component so its own useHttp runs at
                 setup time. Re-keyed by URL so it remounts on re-open. -->
            <CreateInDialogForm
                v-else-if="fetched"
                :key="url"
                :title="fetched.title"
                :components="fetched.components"
                :actions="fetched.actions"
                :record="fetched.record"
                @cancel="onCancel"
                @created="onCreated"
            />
        </DialogContent>
    </Dialog>
</template>
