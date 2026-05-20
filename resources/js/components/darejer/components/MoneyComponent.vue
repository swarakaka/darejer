<script setup lang="ts">
import { Check, ChevronDown, Loader2, Plus } from 'lucide-vue-next'
import { computed, ref, watch, onMounted } from 'vue'
import CreateInDialog from '@/components/darejer/CreateInDialog.vue'
import FieldWrapper from '@/components/darejer/FieldWrapper.vue'
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command'
import { Input } from '@/components/ui/input'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip'
import { useDataUrl } from '@/composables/useDataUrl'
import useTranslation from '@/composables/useTranslation'
import type { DarejerComponent } from '@/types/darejer'

const { __ } = useTranslation()

const props = defineProps<{
  component: DarejerComponent
  record: Record<string, unknown>
  errors: Record<string, string>
  formData?: Record<string, unknown>
}>()

const emit = defineEmits<{ (e: 'update', name: string, value: unknown): void }>()

type CurrencyRow = Record<string, unknown>

const fallbackDecimals = computed<number>(() => (props.component.decimals as number | undefined) ?? 2)
const allowNegative = computed<boolean>(() => (props.component.allowNegative as boolean | undefined) ?? true)

// ── Currency picker state ───────────────────────────────────────────────────
const currencyDataUrl = computed<string | undefined>(() => props.component.currencyDataUrl as string | undefined)
const currencyKeyField = computed<string>(() => (props.component.currencyKeyField as string | undefined) ?? 'id')
const currencyLabelField = computed<string>(() => (props.component.currencyLabelField as string | undefined) ?? 'code')
const currencyDecimalsField = computed<string>(
  () => (props.component.currencyDecimalsField as string | undefined) ?? 'minor_units',
)
const currencyValueField = computed<string | undefined>(() => props.component.currencyValueField as string | undefined)

const hasPicker = computed(() => !!currencyDataUrl.value && !!currencyValueField.value)

const selectedCurrency = ref<CurrencyRow | null>(null)
const currencies = ref<CurrencyRow[]>([])
const pickerOpen = ref(false)
const pickerSearch = ref('')
const pickerLoading = ref(false)

const { load: loadCurrencies } = useDataUrl<CurrencyRow>(currencyDataUrl.value, {
  perPage: 200,
})

const { load: loadCurrencyById } = useDataUrl<CurrencyRow>(currencyDataUrl.value, {
  perPage: 1,
})

async function fetchCurrencies(): Promise<void> {
  if (!currencyDataUrl.value) return
  pickerLoading.value = true
  const result = await loadCurrencies({ search: pickerSearch.value })
  pickerLoading.value = false
  if (result) currencies.value = result.data
}

async function resolveSelectedCurrency(id: unknown): Promise<void> {
  if (!currencyDataUrl.value || id === null || id === undefined || id === '') {
    selectedCurrency.value = null
    return
  }
  const result = await loadCurrencyById({ ids: [String(id)] })
  if (result && result.data.length > 0) {
    selectedCurrency.value = result.data[0]
  }
}

const decimals = computed<number>(() => {
  if (selectedCurrency.value) {
    const minor = selectedCurrency.value[currencyDecimalsField.value]
    if (typeof minor === 'number') return minor
    if (typeof minor === 'string' && minor !== '') {
      const n = Number(minor)
      if (Number.isFinite(n)) return n
    }
  }
  return fallbackDecimals.value
})

// ── Separators (locale-aware with optional override) ────────────────────────
const localeParts = computed(() =>
  new Intl.NumberFormat(undefined, {
    minimumFractionDigits: decimals.value,
    maximumFractionDigits: decimals.value,
  }).formatToParts(12345.6),
)

const localeDecimalSeparator = computed<string>(() => localeParts.value.find((p) => p.type === 'decimal')?.value ?? '.')

const localeGroupSeparator = computed<string>(() => localeParts.value.find((p) => p.type === 'group')?.value ?? ',')

const decimalSeparator = computed<string>(
  () => (props.component.decimalSeparator as string | undefined) ?? localeDecimalSeparator.value,
)

const groupSeparator = computed<string>(
  () => (props.component.thousandsSeparator as string | undefined) ?? localeGroupSeparator.value,
)

// ── Numeric value plumbing ──────────────────────────────────────────────────
function toNumber(value: unknown): number | null {
  if (value === null || value === undefined || value === '') return null
  if (typeof value === 'number') return Number.isFinite(value) ? value : null
  if (typeof value !== 'string') return null
  const cleaned = value.replace(new RegExp(`\\${groupSeparator.value}`, 'g'), '').replace(decimalSeparator.value, '.')
  const n = Number(cleaned)
  return Number.isFinite(n) ? n : null
}

function formatGrouped(n: number): string {
  const fixed = n.toFixed(decimals.value)
  const [intPart, fracPart] = fixed.split('.')
  const withGroups = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, groupSeparator.value)
  return fracPart !== undefined ? `${withGroups}${decimalSeparator.value}${fracPart}` : withGroups
}

function formatRaw(n: number): string {
  return n.toFixed(decimals.value).replace('.', decimalSeparator.value)
}

const source = computed(() => props.formData ?? props.record)
const initialNumeric = toNumber(source.value[props.component.name] ?? props.component.default)

const numericValue = ref<number | null>(initialNumeric)
const focused = ref(false)
const displayValue = ref<string>(initialNumeric === null ? '' : formatGrouped(initialNumeric))

// Reformat the displayed value whenever decimals change (e.g. picking a
// new currency with different minor_units while the field has a value).
watch(decimals, () => {
  if (focused.value || numericValue.value === null) return
  displayValue.value = formatGrouped(numericValue.value)
})

watch(
  () => source.value[props.component.name],
  (incoming) => {
    if (focused.value) return
    const n = toNumber(incoming)
    numericValue.value = n
    displayValue.value = n === null ? '' : formatGrouped(n)
  },
)

// Reflect external changes to the sibling currency_id field (e.g. another
// component editing the same form, or a record load).
if (currencyValueField.value) {
  watch(
    () => source.value[currencyValueField.value as string],
    (incoming) => {
      if (selectedCurrency.value && String(selectedCurrency.value[currencyKeyField.value]) === String(incoming)) {
        return
      }
      void resolveSelectedCurrency(incoming)
    },
  )
}

onMounted(() => {
  if (hasPicker.value && currencyValueField.value) {
    const id = source.value[currencyValueField.value]
    if (id !== null && id !== undefined && id !== '') {
      void resolveSelectedCurrency(id)
    }
  }
})

watch(pickerOpen, (open) => {
  if (open) void fetchCurrencies()
})

watch(pickerSearch, () => {
  if (pickerOpen.value) void fetchCurrencies()
})

function selectCurrency(row: CurrencyRow) {
  selectedCurrency.value = row
  pickerOpen.value = false
  pickerSearch.value = ''

  const id = row[currencyKeyField.value]
  if (currencyValueField.value && id !== undefined) {
    emit('update', currencyValueField.value, id)
  }
  // Re-emit the amount so the form sees a value consistent with the new
  // decimals (the formatted display rounds to the new fraction digits).
  if (numericValue.value !== null) {
    const rounded = Number(numericValue.value.toFixed(decimals.value))
    if (rounded !== numericValue.value) {
      numericValue.value = rounded
      emit('update', props.component.name, rounded)
    }
  }
}

const selectedKey = computed<string | null>(() =>
  selectedCurrency.value ? String(selectedCurrency.value[currencyKeyField.value] ?? '') : null,
)

// ── Static suffix (when picker is not used) ─────────────────────────────────
function resolvePath(obj: Record<string, unknown>, path: string): unknown {
  return path
    .split('.')
    .reduce<unknown>(
      (acc, key) => (acc && typeof acc === 'object' ? (acc as Record<string, unknown>)[key] : undefined),
      obj,
    )
}

const pickerSuffix = computed<string | null>(() => {
  if (!selectedCurrency.value) return null
  const labelField = currencyLabelField.value
  const value = selectedCurrency.value[labelField]
  return value === null || value === undefined ? null : String(value)
})

const staticSuffix = computed<string | null>(() => {
  const field = props.component.currencyField as string | undefined
  if (field) {
    const resolved = resolvePath(source.value, field)
    if (resolved) return String(resolved)
  }
  const currency = props.component.currency as string | undefined
  if (currency) return currency
  const suffix = props.component.suffix as string | undefined
  return suffix ?? null
})

const hasPrefix = computed(() => !!props.component.prefix)
const hasInputSuffix = computed(() => !hasPicker.value && !!staticSuffix.value)

// Optional clickable suffix that opens an inline dialog (e.g. "+ Add
// Exchange Rate"). When set, the static suffix is hidden in favor of
// the action button.
const suffixActionUrl = computed(() => (props.component.suffixActionUrl as string | undefined) ?? null)
const suffixActionTooltip = computed(() => (props.component.suffixActionTooltip as string | undefined) ?? null)
const hasSuffixAction = computed(() => suffixActionUrl.value !== null && !hasPicker.value)

const suffixDialogOpen = ref(false)
function openSuffixDialog() {
  if (!suffixActionUrl.value) return
  suffixDialogOpen.value = true
}

// ── Editing handlers ────────────────────────────────────────────────────────
function sanitizeTyping(raw: string): string {
  const ds = decimalSeparator.value
  const allowed = new RegExp(`[^0-9\\${ds}${allowNegative.value ? '-' : ''}]`, 'g')
  let cleaned = raw.replace(allowed, '')

  if (allowNegative.value) {
    const negative = cleaned.startsWith('-')
    cleaned = cleaned.replace(/-/g, '')
    if (negative) cleaned = `-${cleaned}`
  }

  const firstSep = cleaned.indexOf(ds)
  if (firstSep !== -1) {
    const before = cleaned.slice(0, firstSep + 1)
    const after = cleaned.slice(firstSep + 1).replace(new RegExp(`\\${ds}`, 'g'), '')
    cleaned = `${before}${after.slice(0, decimals.value)}`
  }

  return cleaned
}

function clamp(n: number): number {
  const min = props.component.min as number | undefined
  const max = props.component.max as number | undefined
  if (typeof min === 'number' && n < min) return min
  if (typeof max === 'number' && n > max) return max
  return n
}

function onFocus() {
  focused.value = true
  displayValue.value = numericValue.value === null ? '' : formatRaw(numericValue.value)
}

function onInput(e: Event) {
  const target = e.target as HTMLInputElement
  const cleaned = sanitizeTyping(target.value)
  if (cleaned !== target.value) {
    target.value = cleaned
  }
  displayValue.value = cleaned

  const n = toNumber(cleaned)
  if (n === null) {
    if (cleaned === '') {
      numericValue.value = null
      emit('update', props.component.name, null)
    }
    return
  }
  numericValue.value = n
  emit('update', props.component.name, n)
}

function onBlur() {
  focused.value = false
  if (numericValue.value === null) {
    displayValue.value = ''
    return
  }
  const clamped = clamp(numericValue.value)
  if (clamped !== numericValue.value) {
    numericValue.value = clamped
    emit('update', props.component.name, clamped)
  }
  displayValue.value = formatGrouped(clamped)
}
</script>

<template>
  <FieldWrapper :component="component" :record="record" :errors="errors" :form-data="formData">
    <template #default="{ hasError }">
      <div class="relative flex w-full items-center">
        <span
          v-if="hasPrefix"
          class="border-paper-300 bg-paper-50 text-ink-500 pointer-events-none absolute inset-y-0 inset-s-0 z-10 flex max-w-[40%] items-center truncate rounded-s-md border-e px-2.5 text-sm whitespace-nowrap select-none"
        >
          {{ component.prefix }}
        </span>

        <Input
          :id="component.name"
          :name="component.name"
          type="text"
          inputmode="decimal"
          :placeholder="(component.placeholder as string) ?? ''"
          :value="displayValue"
          :readonly="component.readonly as boolean"
          :disabled="component.disabled as boolean"
          :autofocus="component.autofocus as boolean"
          class="w-full text-start tabular-nums"
          :class="[
            hasError ? 'border-danger-600' : '',
            hasPrefix ? 'ps-20' : '',
            hasInputSuffix && !hasSuffixAction ? 'pe-20' : '',
            hasSuffixAction ? 'pe-8' : '',
            hasPicker ? 'rounded-e-none' : '',
          ]"
          @focus="onFocus"
          @blur="onBlur"
          @input="onInput"
        />

        <span
          v-if="hasInputSuffix && !hasSuffixAction"
          class="border-paper-300 bg-paper-50 text-ink-500 pointer-events-none absolute inset-y-0 inset-e-0 z-10 flex max-w-[40%] items-center truncate rounded-e-md border-s px-2.5 text-sm whitespace-nowrap select-none"
        >
          {{ staticSuffix }}
        </span>

        <!-- Suffix action: clickable icon button sitting INSIDE the input. -->
        <TooltipProvider v-if="hasSuffixAction" :delay-duration="0">
          <Tooltip>
            <TooltipTrigger as-child>
              <button
                type="button"
                class="text-ink-400 hover:bg-paper-200 hover:text-ink-900 focus:ring-brand-500 absolute inset-y-0 inset-e-1 z-10 my-auto flex h-6 w-6 items-center justify-center rounded-sm transition-colors focus:ring-2 focus:outline-none"
                :aria-label="suffixActionTooltip ?? 'Add'"
                @click="openSuffixDialog"
              >
                <Plus class="h-3.5 w-3.5" />
              </button>
            </TooltipTrigger>
            <TooltipContent v-if="suffixActionTooltip" side="top" class="text-xs">
              {{ suffixActionTooltip }}
            </TooltipContent>
          </Tooltip>
        </TooltipProvider>

        <CreateInDialog
          v-if="hasSuffixAction"
          v-model:open="suffixDialogOpen"
          :url="suffixActionUrl as string"
          mode="page"
        />

        <Popover v-if="hasPicker" v-model:open="pickerOpen">
          <PopoverTrigger as-child>
            <button
              type="button"
              :disabled="component.disabled as boolean"
              class="bg-paper-50 text-ink-700 hover:bg-paper-100 focus:border-brand-500 flex h-8 min-w-16 items-center gap-1 rounded-e-[2px] border border-s-0 border-(--input-border) px-2 text-[13px] transition-colors focus:shadow-[inset_0_0_0_1px_var(--color-brand-500)] focus:ring-0 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
              :class="hasError ? 'border-danger-600' : ''"
            >
              <span class="truncate font-medium">
                {{ pickerSuffix ?? __('—') }}
              </span>
              <ChevronDown class="text-ink-400 h-3 w-3 shrink-0" />
            </button>
          </PopoverTrigger>
          <PopoverContent class="w-56 p-0" align="end">
            <Command :should-filter="false">
              <CommandInput v-model="pickerSearch" :placeholder="__('Search currencies…')" class="h-9" />
              <CommandList>
                <div v-if="pickerLoading" class="text-ink-500 flex items-center justify-center gap-1.5 py-3 text-xs">
                  <Loader2 class="h-3 w-3 animate-spin" />
                  {{ __('Loading…') }}
                </div>
                <CommandEmpty v-else>{{ __('No currencies found.') }}</CommandEmpty>
                <CommandGroup>
                  <CommandItem
                    v-for="row in currencies"
                    :key="String(row[currencyKeyField] ?? '')"
                    :value="String(row[currencyKeyField] ?? '')"
                    @select="selectCurrency(row)"
                  >
                    <Check
                      class="me-2 h-4 w-4"
                      :class="selectedKey === String(row[currencyKeyField] ?? '') ? 'opacity-100' : 'opacity-0'"
                    />
                    <span class="font-medium">{{ row[currencyLabelField] }}</span>
                    <span v-if="row[currencyDecimalsField] !== undefined" class="text-ink-400 ms-auto text-xs">
                      {{ row[currencyDecimalsField] }}d
                    </span>
                  </CommandItem>
                </CommandGroup>
              </CommandList>
            </Command>
          </PopoverContent>
        </Popover>
      </div>
    </template>
  </FieldWrapper>
</template>
