<script setup lang="ts">
import { computed } from 'vue'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Trash2 } from 'lucide-vue-next'
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
  currency: { code: string; symbol: string | null }
}>()

const emit = defineEmits<{
  'update:cart': [value: CartLine[]]
}>()

const { __ } = useTranslation()

const localized = (name: Record<string, string> | string) =>
  typeof name === 'object' ? Object.values(name)[0] : name

function lineTotal(line: CartLine): number {
  const qty = parseFloat(line.qty) || 0
  const rate = parseFloat(line.rate) || 0
  const disc = parseFloat(line.discount_pct) || 0
  return qty * rate * (1 - disc / 100)
}

function update(index: number, field: keyof Pick<CartLine, 'qty' | 'rate' | 'discount_pct'>, value: string) {
  const next = [...props.cart]
  next[index] = { ...next[index], [field]: value }
  emit('update:cart', next)
}

function remove(index: number) {
  const next = [...props.cart]
  next.splice(index, 1)
  emit('update:cart', next)
}

const empty = computed(() => props.cart.length === 0)
</script>

<template>
  <div class="rounded-sm border border-ink-200 bg-white">
    <div class="border-b border-ink-200 px-3 py-2 text-[12px] font-semibold uppercase tracking-wider text-ink-600">
      {{ __('Cart') }} · {{ cart.length }}
    </div>

    <div class="max-h-[calc(100vh-22rem)] overflow-y-auto">
      <div
        v-if="empty"
        class="p-8 text-center text-[13px] text-ink-500"
      >
        {{ __('Scan a barcode or tap an item to start a sale.') }}
      </div>

      <table v-else class="w-full text-[13px]">
        <thead class="sticky top-0 bg-paper-100 text-[11px] uppercase tracking-wider text-ink-500">
          <tr>
            <th class="px-3 py-2 text-start">{{ __('Item') }}</th>
            <th class="px-2 py-2 text-end" style="width: 5rem">{{ __('Qty') }}</th>
            <th class="px-2 py-2 text-end" style="width: 6rem">{{ __('Price') }}</th>
            <th class="px-2 py-2 text-end" style="width: 4rem">{{ __('Disc%') }}</th>
            <th class="px-2 py-2 text-end" style="width: 6rem">{{ __('Total') }}</th>
            <th class="px-1 py-2" style="width: 2rem"></th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="(line, idx) in cart"
            :key="`${line.item.id}-${idx}`"
            class="border-t border-ink-100 hover:bg-paper-50"
          >
            <td class="px-3 py-2">
              <div class="font-medium text-ink-900">{{ localized(line.item.name) }}</div>
              <div class="text-[11px] text-ink-500">{{ line.item.code }}</div>
            </td>
            <td class="px-1 py-1">
              <Input
                :model-value="line.qty"
                type="number"
                step="0.001"
                min="0"
                class="h-7 text-end"
                @update:model-value="(v) => update(idx, 'qty', String(v))"
              />
            </td>
            <td class="px-1 py-1">
              <Input
                :model-value="line.rate"
                type="number"
                step="0.01"
                min="0"
                class="h-7 text-end"
                @update:model-value="(v) => update(idx, 'rate', String(v))"
              />
            </td>
            <td class="px-1 py-1">
              <Input
                :model-value="line.discount_pct"
                type="number"
                step="0.1"
                min="0"
                max="100"
                class="h-7 text-end"
                @update:model-value="(v) => update(idx, 'discount_pct', String(v))"
              />
            </td>
            <td class="px-2 py-2 text-end font-semibold">
              {{ lineTotal(line).toFixed(2) }}
            </td>
            <td class="px-1 py-2">
              <Button variant="ghost" size="icon-sm" @click="remove(idx)">
                <Trash2 class="size-3.5 text-danger-500" />
              </Button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
