<script setup lang="ts">
import { computed } from 'vue'
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
const rateStep = computed(() => (decimals.value > 0 ? '0.' + '0'.repeat(decimals.value - 1) + '1' : '1'))

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
              <div class="text-[15px] font-bold tabular-nums text-ink-900">{{ lineTotal(line).toFixed(decimals) }}</div>
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
                :model-value="line.rate"
                type="number"
                inputmode="decimal"
                :step="rateStep"
                min="0"
                class="h-11 text-end text-[15px] tabular-nums"
                :placeholder="__('Price')"
                @update:model-value="(v) => update(idx, 'rate', String(v))"
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
