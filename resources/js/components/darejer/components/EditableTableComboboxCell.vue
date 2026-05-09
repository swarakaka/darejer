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
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import { ChevronsUpDown, Loader2, ImageIcon } from 'lucide-vue-next'
import useTranslation from '@/composables/useTranslation'

const { __ } = useTranslation()

interface ComboboxColumn {
  field: string
  dataUrl?: string
  keyField?: string
  labelField?: string
  labelFields?: string[]
  searchFields?: string[]
  subLabelField?: string
  imageField?: string
  optionFields?: string[]
  fillFrom?: Record<string, string> | null
  /** filterParam => formField. Sibling form values become `filters[param]`. */
  filtersFrom?: Record<string, string> | null
  placeholder?: string
}

type Record_ = Record<string, unknown>

const props = defineProps<{
  column: ComboboxColumn
  modelValue: unknown
  disabled?: boolean
  /** Surrounding form data — read for filtersFrom mapping. */
  formData?: Record<string, unknown>
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: unknown): void
  /** Emitted when the user picks an option — host applies fillFrom mapping. */
  (e: 'select', record: Record_): void
}>()

const open = ref(false)
const search = ref('')
const records = ref<Record_[]>([])
const page = ref(1)
const hasMore = ref(false)

/** Cache of records keyed by their key value, so labels survive page reloads
 *  even when the option isn't on the first page. */
const cache = ref<Record<string, Record_>>({})

const keyField = computed(() => props.column.keyField ?? 'id')
const labelField = computed(() => props.column.labelField ?? 'name')
const labelFields = computed(() => props.column.labelFields ?? null)
const subLabelField = computed(() => props.column.subLabelField)
const imageField = computed(() => props.column.imageField)

/** Match Combobox's server-side join character. Hardcoded so it survives
 *  Laravel's TrimStrings middleware. */
const LABEL_SEPARATOR = ' — '

function composeLabel(record: Record_): string {
  if (labelFields.value && labelFields.value.length > 0) {
    const parts: string[] = []
    for (const f of labelFields.value) {
      const v = record[f]
      if (v === null || v === undefined || v === '') continue
      parts.push(String(v))
    }
    return parts.join(LABEL_SEPARATOR)
  }
  return String(record[labelField.value] ?? '')
}

const { load, http } = useDataUrl<Record_>(props.column.dataUrl, {
  perPage: 25,
  keyField: keyField.value,
  labelField: labelField.value,
  labelFields: labelFields.value ?? undefined,
  searchFields: props.column.searchFields ?? undefined,
  fields: props.column.optionFields ?? [],
})

/**
 * Build the `filters[param]=value` payload from sibling form fields.
 * Empty / null / undefined values are dropped so the request stays clean.
 */
const derivedFilters = computed<Record<string, string>>(() => {
  const map = props.column.filtersFrom
  if (!map || !props.formData) return {}
  const out: Record<string, string> = {}
  for (const [param, formField] of Object.entries(map)) {
    const v = props.formData[formField]
    if (v === null || v === undefined || v === '') continue
    out[param] = String(v)
  }
  return out
})

async function fetchOptions(reset = false): Promise<void> {
  if (reset) {
    page.value = 1
    records.value = []
  }
  const result = await load({
    search: search.value,
    page: page.value,
    filters: derivedFilters.value,
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
  page.value = result.current_page + 1
}

/**
 * Fetch the currently-bound record by id when it isn't yet cached — keeps
 * the trigger from showing the raw id when the selection lives outside
 * the first paginated page (typical on edit screens).
 */
async function resolveSelectedRecord(): Promise<void> {
  const key = selectedKey.value
  if (key === '' || cache.value[key]) return

  // Resolving the bound record is for label rendering only — bypass
  // derivedFilters so a row that was saved before the filter became
  // active still resolves its label.
  const result = await load({
    ids: [key],
    page: 1,
    perPage: 1,
    filters: {},
  })
  if (!result) return

  for (const r of result.data) {
    const k = String(r[keyField.value] ?? '')
    if (k !== '') cache.value[k] = r
  }
}

onMounted(async () => {
  await resolveSelectedRecord()
  fetchOptions(true)
})
watch(search, () => fetchOptions(true))
// Refetch when a filtersFrom-bound form field changes — e.g. picking a
// "From Warehouse" should narrow the items list.
watch(derivedFilters, () => fetchOptions(true), { deep: true })

const selectedKey = computed(() =>
  props.modelValue === null || props.modelValue === undefined || props.modelValue === ''
    ? ''
    : String(props.modelValue),
)

const selectedRecord = computed<Record_ | null>(() =>
  selectedKey.value ? (cache.value[selectedKey.value] ?? null) : null,
)

const triggerLabel = computed(() => {
  if (selectedRecord.value) {
    const composed = composeLabel(selectedRecord.value)
    return composed !== '' ? composed : selectedKey.value
  }
  if (selectedKey.value) return selectedKey.value
  return props.column.placeholder ?? __('Select…')
})

function pick(record: Record_) {
  const key = String(record[keyField.value] ?? '')
  cache.value[key] = record
  emit('update:modelValue', key)
  emit('select', record)
  open.value = false
}
</script>

<template>
  <Popover :open="open" @update:open="open = $event">
    <PopoverTrigger as-child>
      <button
        type="button"
        :disabled="disabled"
        class="flex h-full w-full items-center justify-between border-none bg-transparent px-2.5 text-start text-sm transition-colors duration-100 outline-none focus:bg-brand-50 disabled:cursor-not-allowed disabled:opacity-50"
      >
        <span class="truncate" :class="selectedKey ? 'text-ink-900' : `text-ink-400`">
          {{ triggerLabel }}
        </span>
        <ChevronsUpDown class="ms-1 h-3.5 w-3.5 shrink-0 text-ink-300" />
      </button>
    </PopoverTrigger>

    <PopoverContent class="w-80 p-0" align="start">
      <Command :should-filter="false">
        <CommandInput
          v-model="search"
          :placeholder="__('Search…')"
          class="h-8 border-b border-paper-200 text-sm"
        />

        <CommandList class="max-h-72 overflow-y-auto">
          <CommandEmpty class="py-4 text-center text-sm text-ink-400">
            <span v-if="http.processing" class="flex items-center justify-center gap-2">
              <Loader2 class="h-3.5 w-3.5 animate-spin" /> {{ __('Loading…') }}
            </span>
            <span v-else>{{ __('No results found.') }}</span>
          </CommandEmpty>

          <CommandGroup>
            <CommandItem
              v-for="record in records"
              :key="String(record[keyField] ?? '')"
              :value="String(record[keyField] ?? '')"
              class="flex cursor-pointer items-center gap-2.5 px-2.5 py-1.5 text-sm"
              @select="pick(record)"
            >
              <!-- Image / placeholder -->
              <div
                v-if="imageField"
                class="flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-sm bg-paper-100"
              >
                <img
                  v-if="record[imageField]"
                  :src="String(record[imageField])"
                  :alt="String(record[labelField] ?? '')"
                  class="h-full w-full object-cover"
                />
                <ImageIcon v-else class="h-3.5 w-3.5 text-ink-300" />
              </div>

              <div class="min-w-0 flex-1">
                <div class="truncate font-medium text-ink-900">
                  {{ composeLabel(record) }}
                </div>
                <div
                  v-if="
                    subLabelField &&
                    record[subLabelField] !== undefined &&
                    record[subLabelField] !== null &&
                    record[subLabelField] !== ''
                  "
                  class="truncate text-[11px] text-ink-400"
                >
                  {{ record[subLabelField] }}
                </div>
              </div>
            </CommandItem>

            <CommandItem
              v-if="hasMore"
              value="__load_more__"
              class="h-7 cursor-pointer justify-center px-2.5 text-xs text-ink-400"
              @select="fetchOptions(false)"
            >
              {{ __('Load more…') }}
            </CommandItem>
          </CommandGroup>
        </CommandList>
      </Command>
    </PopoverContent>
  </Popover>
</template>
