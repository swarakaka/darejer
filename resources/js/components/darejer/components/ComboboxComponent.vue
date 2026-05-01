<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useHttp }          from '@inertiajs/vue3'
import { useDataUrl }       from '@/composables/useDataUrl'
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
}                           from '@/components/ui/command'
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
}                           from '@/components/ui/popover'
import { Badge }            from '@/components/ui/badge'
import { Check, ChevronsUpDown, Plus, X, Loader2 } from 'lucide-vue-next'
import FieldWrapper         from '@/components/darejer/FieldWrapper.vue'
import CreateInDialog       from '@/components/darejer/CreateInDialog.vue'
import useTranslation       from '@/composables/useTranslation'
import type { DarejerComponent } from '@/types/darejer'

const { __ } = useTranslation()

const props = defineProps<{
    component: DarejerComponent
    record:    Record<string, unknown>
    errors:    Record<string, string>
    formData?: Record<string, unknown>
}>()

const emit = defineEmits<{
    (e: 'update', name: string, value: unknown): void
    (e: 'prefill', fields: Record<string, unknown>): void
}>()

type Option = { value: string; label: string }

const open      = ref(false)
const search    = ref('')
const options   = ref<Option[]>([])
const page      = ref(1)
const hasMore   = ref(false)

// Shared data-loading composable — always goes through Inertia useHttp.
const { load, http } = useDataUrl<Option>(
    props.component.dataUrl as string | undefined,
    {
        perPage:      50,
        combobox:     true,
        keyField:     (props.component.keyField     as string)               ?? 'id',
        labelField:   (props.component.labelField   as string)               ?? 'name',
        labelFields:  (props.component.labelFields  as string[] | undefined) ?? undefined,
        searchFields: (props.component.searchFields as string[] | undefined) ?? undefined,
    },
)

const rawValue  = (props.formData ?? props.record)[props.component.name]
    ?? props.component.default ?? null

const selected  = ref<string[]>(
    Array.isArray(rawValue)
        ? rawValue.map(String)
        : rawValue != null
            ? [String(rawValue)]
            : []
)

const isMultiple = computed(() => !!props.component.multiple)

const staticOptions = computed(() =>
    (props.component.staticOptions as Option[] | undefined) ?? null
)

async function fetchOptions(reset = false): Promise<void> {
    // Static options: filter locally, no network.
    if (staticOptions.value) {
        options.value = staticOptions.value.filter(o =>
            !search.value ||
            o.label.toLowerCase().includes(search.value.toLowerCase())
        )
        hasMore.value = false
        return
    }

    if (reset) {
        page.value    = 1
        options.value = []
    }

    const result = await load({
        search: search.value,
        page:   page.value,
    })

    if (!result) return

    const items = result.data
    if (reset) {
        options.value = items
    } else {
        options.value.push(...items)
    }
    hasMore.value = result.current_page < result.last_page
    page.value    = result.current_page + 1
}

onMounted(() => fetchOptions(true))

watch(search, () => fetchOptions(true))

function isSelected(value: string): boolean {
    return selected.value.includes(value)
}

function toggle(value: string) {
    if (isMultiple.value) {
        if (isSelected(value)) {
            selected.value = selected.value.filter(v => v !== value)
        } else {
            selected.value = [...selected.value, value]
        }
    } else {
        selected.value = isSelected(value) ? [] : [value]
        open.value = false
    }

    emit('update', props.component.name,
        isMultiple.value ? selected.value : (selected.value[0] ?? null)
    )

    maybePrefillFromUrl(selected.value[0] ?? null)
}

const prefillHttp = useHttp<Record<string, never>, Record<string, unknown>>()

/**
 * When `prefillUrl` is set on a single-select combobox, picking a value
 * fetches `${prefillUrl}?id=<value>` and emits a `prefill` event with the
 * response. The host form merges those fields into its state in place —
 * no URL change, no full-page reload, other unsaved input is preserved.
 */
function maybePrefillFromUrl(value: string | null) {
    const url = props.component.prefillUrl as string | undefined
    if (!url || isMultiple.value || !value) return

    const sep = url.includes('?') ? '&' : '?'
    prefillHttp.get(`${url}${sep}id=${encodeURIComponent(value)}`, {
        onSuccess: (data) => {
            if (data && typeof data === 'object') {
                emit('prefill', data as Record<string, unknown>)
            }
        },
    })
}

function labelFor(value: string): string {
    return options.value.find(o => o.value === value)?.label ?? value
}

const selectedLabels = computed(() =>
    selected.value.map(v => labelFor(v))
)

const triggerLabel = computed(() => {
    if (selected.value.length === 0) {
        return (props.component.placeholder as string) ?? __('Select…')
    }
    if (!isMultiple.value) return selectedLabels.value[0]
    return __(':count selected_short', { count: selected.value.length })
})

const isClearable = computed(() =>
    props.component.clearable !== false && !props.component.disabled
)

const canShowClear = computed(() =>
    isClearable.value && selected.value.length > 0
)

function clear(event?: Event) {
    event?.stopPropagation()
    event?.preventDefault()
    if (selected.value.length === 0) return
    selected.value = []
    emit('update', props.component.name, isMultiple.value ? [] : null)
}

// Local create dialog — fetched and rendered as part of THIS page's tree
// so the parent screen stays mounted (and visible) underneath.
const createDialogOpen = ref(false)
const createDialogUrl  = ref('')
// 'form' = dedicated reusable Form endpoint (returns plain JSON schema)
// 'page' = full create-page endpoint (returns Inertia page JSON)
const createDialogMode = ref<'form' | 'page'>('page')

function extractIdFromUrl(url: string | null | undefined): string | null {
    if (!url) return null
    const path = url.split('?')[0].split('#')[0]
    const segments = path.split('/').filter(Boolean)
    for (let i = segments.length - 1; i >= 0; i--) {
        if (/^\d+$/.test(segments[i])) return segments[i]
    }
    return null
}

function openAddDialog() {
    open.value = false
    // Prefer the reusable Form endpoint when the controller declares one
    // via createForm(). Fall back to the full create page otherwise.
    const formUrl = props.component.formUrl as string | undefined
    const addUrl  = props.component.addUrl  as string | undefined
    const target = formUrl ?? addUrl
    if (!target) return
    createDialogUrl.value = target
    createDialogMode.value = formUrl ? 'form' : 'page'
    createDialogOpen.value = true
}

async function onCreateDialogSaved(payload: { url: string | null; flash: unknown }) {
    // Re-fetch sorted by id desc so the newest record is first regardless of
    // server-side default ordering.
    const result = await load({
        search: search.value,
        page:   1,
        sort:   'id',
        order:  'desc',
    })
    if (result) {
        options.value = result.data
        hasMore.value = result.current_page < result.last_page
        page.value    = result.current_page + 1
    }

    const flash = payload.flash as { created_id?: string | number } | null
    const flashId = flash?.created_id
    const candidate = flashId != null
        ? String(flashId)
        : extractIdFromUrl(payload.url) ?? options.value[0]?.value ?? null

    if (candidate && !isSelected(candidate)) {
        toggle(candidate)
    }
}
</script>

<template>
    <FieldWrapper :component="component" :record="record" :errors="errors" :form-data="formData">
        <template #default="{ hasError }">
            <Popover :open="open" @update:open="open = $event">
                <div class="relative">
                    <PopoverTrigger as-child>
                        <button
                            type="button"
                            :disabled="(component.disabled as boolean)"
                            class="flex items-center justify-between w-full h-8 px-2.5
                                   text-sm text-start bg-white border rounded-sm
                                   text-ink-900 transition-colors duration-100
                                   focus:outline-none focus:ring-0
                                   disabled:opacity-50 disabled:cursor-not-allowed"
                            :class="[
                                hasError ? 'border-danger-600' : 'border-paper-300',
                                open ? 'border-brand-500' : '',
                                canShowClear ? 'pe-7' : '',
                            ]"
                        >
                            <!-- Single selected -->
                            <span
                                v-if="!isMultiple || selected.length === 0"
                                :class="selected.length === 0 ? 'text-ink-400' : 'text-ink-900'"
                                class="truncate"
                            >
                                {{ triggerLabel }}
                            </span>

                            <!-- Multiple selected: badges -->
                            <div v-else class="flex items-center gap-1 flex-wrap overflow-hidden">
                                <Badge
                                    v-for="label in selectedLabels.slice(0, 3)"
                                    :key="label"
                                    variant="secondary"
                                    class="text-xs px-1.5 py-0 h-5"
                                >
                                    {{ label }}
                                </Badge>
                                <span v-if="selected.length > 3" class="text-xs text-slate-400">
                                    +{{ selected.length - 3 }}
                                </span>
                            </div>

                            <ChevronsUpDown
                                v-if="!canShowClear"
                                class="w-3.5 h-3.5 text-slate-400 shrink-0 ms-1"
                            />
                        </button>
                    </PopoverTrigger>

                    <!-- Clear button: rendered as sibling so it doesn't nest a
                         button inside the trigger. mousedown.stop keeps the
                         popover from opening when the user clicks the X. -->
                    <button
                        v-if="canShowClear"
                        type="button"
                        :aria-label="__('Clear')"
                        class="absolute end-2 top-1/2 -translate-y-1/2 p-0.5 rounded-sm
                               text-ink-400 hover:text-ink-700 hover:bg-paper-100
                               transition-colors focus:outline-none"
                        @mousedown.stop.prevent
                        @click.stop.prevent="clear"
                    >
                        <X class="w-3.5 h-3.5" />
                    </button>
                </div>

                <PopoverContent class="p-0 w-[var(--reka-popper-anchor-width)] min-w-[14rem]" align="start">
                    <Command :should-filter="false">
                        <CommandInput
                            v-if="component.searchable !== false"
                            v-model="search"
                            :placeholder="__('Search…')"
                            class="text-sm h-8 border-b border-slate-200"
                        />

                        <CommandList class="max-h-56 overflow-y-auto">
                            <CommandEmpty class="py-4 text-center text-sm text-slate-400">
                                <span v-if="http.processing" class="flex items-center justify-center gap-2">
                                    <Loader2 class="w-3.5 h-3.5 animate-spin" /> {{ __('Loading…') }}
                                </span>
                                <span v-else>{{ __('No results found.') }}</span>
                            </CommandEmpty>

                            <CommandGroup>
                                <CommandItem
                                    v-for="option in options"
                                    :key="option.value"
                                    :value="option.value"
                                    class="flex items-center gap-2 text-sm h-8 px-2.5 cursor-pointer"
                                    @select="toggle(option.value)"
                                >
                                    <Check
                                        class="w-3.5 h-3.5 shrink-0 text-brand-600 transition-opacity"
                                        :class="isSelected(option.value) ? 'opacity-100' : 'opacity-0'"
                                    />
                                    {{ option.label }}
                                </CommandItem>

                                <!-- Load more -->
                                <CommandItem
                                    v-if="hasMore"
                                    value="__load_more__"
                                    class="text-xs text-slate-400 h-7 px-2.5 cursor-pointer justify-center"
                                    @select="fetchOptions(false)"
                                >
                                    {{ __('Load more…') }}
                                </CommandItem>
                            </CommandGroup>
                        </CommandList>

                        <!-- Sticky "Add new" footer — sits outside CommandList
                             so the scroll only affects the options above it. -->
                        <button
                            v-if="component.addable"
                            type="button"
                            class="flex items-center gap-2 text-sm h-8 px-2.5 w-full text-start
                                   cursor-pointer text-brand-600 font-medium border-t border-slate-200
                                   bg-white hover:bg-paper-50 shrink-0 focus:outline-none"
                            @click="openAddDialog"
                        >
                            <Plus class="w-3.5 h-3.5" />
                            {{ __('Add new…') }}
                        </button>
                    </Command>
                </PopoverContent>
            </Popover>

            <!-- Inline create dialog: rendered as part of THIS page's tree
                 so the parent screen stays mounted and visible underneath. -->
            <CreateInDialog
                v-if="component.addable"
                v-model:open="createDialogOpen"
                :url="createDialogUrl"
                :mode="createDialogMode"
                @created="onCreateDialogSaved"
            />
        </template>
    </FieldWrapper>
</template>
