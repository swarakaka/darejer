<script setup lang="ts">
import { computed, ref } from 'vue'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Minus, Plus, Trash2 } from 'lucide-vue-next'
import useTranslation from '@/composables/useTranslation'

interface PosItem {
  id: number
  code: string
  name: Record<string, string> | string
  selling_price: string
  sales_uom_id: number | null
  default_warehouse_id: number | null
  default_tax_code_sales_id: number | null
  item_type: string
}

interface CartLine {
  item: PosItem
  qty: string
  rate: string
  discount_pct: string
}

const props = defineProps<{
  cart: CartLine[]
  currency: { code: string; symbol: string | null; minor_units: number }
}>()

const emit = defineEmits<{
  'update:cart': [value: CartLine[]]
}>()

const { __, resolveTranslatable: localized } = useTranslation()

function lineTotal(line: CartLine): number {
  const qty = parseFloat(line.qty) || 0
  const rate = parseFloat(line.rate) || 0
  const disc = parseFloat(line.discount_pct) || 0
  return qty * rate * (1 - disc / 100)
}

const decimals = computed(() => props.currency.minor_units ?? 2)
const moneyFormatter = computed(
  () =>
    new Intl.NumberFormat(undefined, {
      minimumFractionDigits: decimals.value,
      maximumFractionDigits: decimals.value,
    }),
)
function fmt(n: number): string {
  return moneyFormatter.value.format(n)
}

// Locale-aware separators for the rate input. We need them so we can
// (a) display the rate grouped (1,200,000) when not focused, and
// (b) parse the user's typed value back to a canonical dot-decimal string
//     no matter whether their locale uses ',' or '.' as the decimal mark.
const sepParts = computed(() =>
  new Intl.NumberFormat(undefined, {
    minimumFractionDigits: decimals.value,
    maximumFractionDigits: decimals.value,
  }).formatToParts(12345.6),
)
const groupSep = computed(() => sepParts.value.find((p) => p.type === 'group')?.value ?? ',')
const decimalSep = computed(() => sepParts.value.find((p) => p.type === 'decimal')?.value ?? '.')

const rateFocusedIdx = ref<number | null>(null)

function rateDisplay(line: CartLine, idx: number): string {
  const n = Number(line.rate)
  if (rateFocusedIdx.value === idx) {
    // While editing, drop grouping but keep the locale decimal mark so the
    // user keeps seeing what they're typing (e.g. ',' on de-DE).
    if (!Number.isFinite(n)) return line.rate
    return String(line.rate).replace('.', decimalSep.value)
  }
  if (!Number.isFinite(n)) return line.rate
  return moneyFormatter.value.format(n)
}

function parseRate(raw: string): string {
  // Strip group chars and normalize the decimal mark to '.'. Anything else
  // that isn't a digit or sign is dropped, so a stray space or letter from
  // a paste doesn't poison the canonical value.
  const groupRe = new RegExp('\\' + groupSep.value, 'g')
  const cleaned = raw
    .replace(groupRe, '')
    .replace(decimalSep.value, '.')
    .replace(/[^0-9.\-]/g, '')
  return cleaned
}

function onRateInput(idx: number, raw: string) {
  update(idx, 'rate', parseRate(raw))
}

function onRateFocus(idx: number) {
  rateFocusedIdx.value = idx
}

function onRateBlur(idx: number) {
  rateFocusedIdx.value = null
  const n = parseFloat(props.cart[idx]?.rate ?? '')
  if (Number.isFinite(n)) {
    update(idx, 'rate', n.toFixed(decimals.value))
  }
}

function update(index: number, field: keyof Pick<CartLine, 'qty' | 'rate' | 'discount_pct'>, value: string) {
  const next = [...props.cart]
  next[index] = { ...next[index], [field]: value }
  emit('update:cart', next)
}

function adjustQty(index: number, delta: number) {
  const current = parseFloat(props.cart[index].qty) || 0
  const next = Math.max(0, current + delta)
  update(index, 'qty', String(next))
}

function remove(index: number) {
  const next = [...props.cart]
  next.splice(index, 1)
  emit('update:cart', next)
}

const empty = computed(() => props.cart.length === 0)
</script>

<template>
  <div class="flex flex-col rounded-sm border border-ink-200 bg-white">
    <div class="border-b border-ink-200 px-4 py-3 text-[12px] font-semibold uppercase tracking-wider text-ink-600">
      {{ __('Cart') }} · {{ cart.length }}
    </div>

    <div class="flex-1 overflow-y-auto">
      <div
        v-if="empty"
        class="p-10 text-center text-[14px] text-ink-500"
      >
        {{ __('Scan a barcode or tap an item to start a sale.') }}
      </div>

      <ul v-else class="divide-y divide-ink-100">
        <li
          v-for="(line, idx) in cart"
          :key="`${line.item.id}-${idx}`"
          class="px-3 py-3"
        >
          <div class="mb-2 flex items-start justify-between gap-2">
            <div class="min-w-0 flex-1">
              <div class="truncate text-[15px] font-semibold text-ink-900">{{ localized(line.item.name) }}</div>
              <div class="text-[12px] text-ink-500">{{ line.item.code }}</div>
            </div>
            <div class="text-end">
              <div class="text-[15px] font-bold tabular-nums text-ink-900">{{ fmt(lineTotal(line)) }}</div>
              <div class="text-[11px] text-ink-500">{{ currency.code }}</div>
            </div>
            <Button variant="ghost" size="icon" class="-mt-1" @click="remove(idx)">
              <Trash2 class="size-4 text-danger-500" />
            </Button>
          </div>

          <div class="grid grid-cols-12 gap-2">
            <!-- Qty stepper -->
            <div class="col-span-6 flex items-stretch overflow-hidden rounded-sm border border-ink-200 sm:col-span-5">
              <button
                type="button"
                class="flex h-11 w-11 items-center justify-center bg-paper-100 text-ink-700 hover:bg-paper-150 active:bg-paper-200"
                @click="adjustQty(idx, -1)"
                :aria-label="__('Decrease')"
              >
                <Minus class="size-4" />
              </button>
              <Input
                :model-value="line.qty"
                type="number"
                inputmode="decimal"
                step="0.001"
                min="0"
                class="h-11 flex-1 rounded-none border-0 text-center text-[15px] font-semibold tabular-nums shadow-none focus-visible:ring-0"
                @update:model-value="(v) => update(idx, 'qty', String(v))"
              />
              <button
                type="button"
                class="flex h-11 w-11 items-center justify-center bg-paper-100 text-ink-700 hover:bg-paper-150 active:bg-paper-200"
                @click="adjustQty(idx, 1)"
                :aria-label="__('Increase')"
              >
                <Plus class="size-4" />
              </button>
            </div>

            <!-- Rate -->
            <div class="col-span-6 sm:col-span-4">
              <Input
                :model-value="rateDisplay(line, idx)"
                type="text"
                inputmode="decimal"
                class="h-11 text-end text-[15px] tabular-nums"
                :placeholder="__('Price')"
                @focus="onRateFocus(idx)"
                @blur="onRateBlur(idx)"
                @update:model-value="(v) => onRateInput(idx, String(v))"
              />
            </div>

            <!-- Discount % -->
            <div class="col-span-12 sm:col-span-3">
              <Input
                :model-value="line.discount_pct"
                type="number"
                inputmode="decimal"
                step="0.5"
                min="0"
                max="100"
                class="h-11 text-end text-[15px] tabular-nums"
                :placeholder="__('Disc%')"
                @update:model-value="(v) => update(idx, 'discount_pct', String(v))"
              />
            </div>
          </div>
        </li>
      </ul>
    </div>
  </div>
</template>
