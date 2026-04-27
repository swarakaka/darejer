<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useDataUrl } from '@/composables/useDataUrl'
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command'
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover'
import { ChevronsUpDown, Loader2, ImageIcon } from 'lucide-vue-next'

interface ComboboxColumn {
    field:        string
    dataUrl?:     string
    keyField?:    string
    labelField?:  string
    priceField?:  string
    imageField?:  string
    optionFields?: string[]
    fillFrom?:    Record<string, string> | null
    placeholder?: string
}

type Record_ = Record<string, unknown>

const props = defineProps<{
    column:    ComboboxColumn
    modelValue: unknown
    disabled?: boolean
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: unknown): void
    /** Emitted when the user picks an option — host applies fillFrom mapping. */
    (e: 'select', record: Record_): void
}>()

const open    = ref(false)
const search  = ref('')
const records = ref<Record_[]>([])
const page    = ref(1)
const hasMore = ref(false)

/** Cache of records keyed by their key value, so labels survive page reloads
 *  even when the option isn't on the first page. */
const cache = ref<Record<string, Record_>>({})

const keyField   = computed(() => props.column.keyField   ?? 'id')
const labelField = computed(() => props.column.labelField ?? 'name')
const priceField = computed(() => props.column.priceField)
const imageField = computed(() => props.column.imageField)

const { load, http } = useDataUrl<Record_>(
    props.column.dataUrl,
    {
        perPage:    25,
        keyField:   keyField.value,
        labelField: labelField.value,
        fields:     props.column.optionFields ?? [],
    },
)

async function fetchOptions(reset = false): Promise<void> {
    if (reset) {
        page.value    = 1
        records.value = []
    }
    const result = await load({
        search: search.value,
        page:   page.value,
    })
    if (!result) return

    const items = result.data
    if (reset) {
        records.value = items
    } else {
        records.value.push(...items)
    }
    for (const r of items) {
        const k = String(r[keyField.value] ?? '')
        if (k !== '') cache.value[k] = r
    }
    hasMore.value = result.current_page < result.last_page
    page.value    = result.current_page + 1
}

onMounted(() => fetchOptions(true))
watch(search, () => fetchOptions(true))

const selectedKey = computed(() =>
    props.modelValue === null || props.modelValue === undefined || props.modelValue === ''
        ? ''
        : String(props.modelValue),
)

const selectedRecord = computed<Record_ | null>(() =>
    selectedKey.value ? cache.value[selectedKey.value] ?? null : null,
)

const triggerLabel = computed(() => {
    if (selectedRecord.value) return String(selectedRecord.value[labelField.value] ?? selectedKey.value)
    if (selectedKey.value)    return selectedKey.value
    return props.column.placeholder ?? 'Select…'
})

function pick(record: Record_) {
    const key = String(record[keyField.value] ?? '')
    cache.value[key] = record
    emit('update:modelValue', key)
    emit('select', record)
    open.value = false
}

function formatPrice(v: unknown): string {
    if (v === null || v === undefined || v === '') return ''
    const n = Number(v)
    if (Number.isNaN(n)) return String(v)
    return n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
</script>

<template>
    <Popover :open="open" @update:open="open = $event">
        <PopoverTrigger as-child>
            <button
                type="button"
                :disabled="disabled"
                class="flex items-center justify-between w-full h-full px-2.5
                       text-sm text-start bg-transparent border-none
                       outline-none focus:bg-brand-50
                       disabled:opacity-50 disabled:cursor-not-allowed
                       transition-colors duration-100"
            >
                <span class="truncate" :class="selectedKey ? 'text-ink-900' : 'text-ink-400'">
                    {{ triggerLabel }}
                </span>
                <ChevronsUpDown class="w-3.5 h-3.5 text-ink-300 shrink-0 ml-1" />
            </button>
        </PopoverTrigger>

        <PopoverContent class="p-0 w-80" align="start">
            <Command :should-filter="false">
                <CommandInput
                    v-model="search"
                    placeholder="Search…"
                    class="text-sm h-8 border-b border-paper-200"
                />

                <CommandList class="max-h-72 overflow-y-auto">
                    <CommandEmpty class="py-4 text-center text-sm text-ink-400">
                        <span v-if="http.processing" class="flex items-center justify-center gap-2">
                            <Loader2 class="w-3.5 h-3.5 animate-spin" /> Loading…
                        </span>
                        <span v-else>No results found.</span>
                    </CommandEmpty>

                    <CommandGroup>
                        <CommandItem
                            v-for="record in records"
                            :key="String(record[keyField] ?? '')"
                            :value="String(record[keyField] ?? '')"
                            class="flex items-center gap-2.5 text-sm px-2.5 py-1.5 cursor-pointer"
                            @select="pick(record)"
                        >
                            <!-- Image / placeholder -->
                            <div
                                v-if="imageField"
                                class="w-8 h-8 shrink-0 rounded-sm bg-paper-100 overflow-hidden
                                       flex items-center justify-center"
                            >
                                <img
                                    v-if="record[imageField]"
                                    :src="String(record[imageField])"
                                    :alt="String(record[labelField] ?? '')"
                                    class="w-full h-full object-cover"
                                />
                                <ImageIcon v-else class="w-3.5 h-3.5 text-ink-300" />
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="truncate font-medium text-ink-900">
                                    {{ String(record[labelField] ?? '') }}
                                </div>
                                <div
                                    v-if="priceField && record[priceField] !== undefined"
                                    class="text-[11px] text-ink-400 tabular-nums"
                                >
                                    {{ formatPrice(record[priceField]) }}
                                </div>
                            </div>
                        </CommandItem>

                        <CommandItem
                            v-if="hasMore"
                            value="__load_more__"
                            class="text-xs text-ink-400 h-7 px-2.5 cursor-pointer justify-center"
                            @select="fetchOptions(false)"
                        >
                            Load more…
                        </CommandItem>
                    </CommandGroup>
                </CommandList>
            </Command>
        </PopoverContent>
    </Popover>
</template>
