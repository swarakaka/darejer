<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import PosNumpad from '@/components/darejer/pos/PosNumpad.vue'
import { Plus, Trash2, Banknote, CreditCard, Building2 } from 'lucide-vue-next'
import useTranslation from '@/composables/useTranslation'

interface Tender {
  tender: 'cash' | 'card' | 'bank_transfer'
  amount: string
  bank_account_id: number | null
  reference: string | null
}

type LocalizedName = Record<string, string> | string

const props = defineProps<{
  open: boolean
  grandTotal: number
  currency: { code: string; symbol: string | null; minor_units: number }
  bankAccounts: Array<{ id: number; code: string; name: LocalizedName }>
  processing: boolean
}>()

const emit = defineEmits<{
  'update:open': [value: boolean]
  confirm: [tenders: Tender[]]
}>()

const { __, resolveTranslatable: localized } = useTranslation()

const tenders = ref<Tender[]>([])
const activeIdx = ref(0)

const decimals = computed(() => props.currency.minor_units ?? 2)
const zeroAmount = computed(() => (0).toFixed(decimals.value))
const amountStep = computed(() => (decimals.value > 0 ? '0.' + '0'.repeat(decimals.value - 1) + '1' : '1'))
// Settlement tolerance shrinks with the smallest representable unit so 0-decimal
// currencies (IQD) compare exactly while 2-decimal ones still allow a 0.01 slop.
const settlementEpsilon = computed(() => (decimals.value > 0 ? Math.pow(10, -decimals.value) : 0.5))

function fmt(n: number): string {
  return n.toFixed(decimals.value)
}

watch(
  () => props.open,
  (open) => {
    if (open) {
      tenders.value = [{ tender: 'cash', amount: fmt(props.grandTotal), bank_account_id: null, reference: null }]
      activeIdx.value = 0
    }
  },
)

const totalPaid = computed(() => tenders.value.reduce((s, t) => s + (parseFloat(t.amount) || 0), 0))
const remaining = computed(() => props.grandTotal - totalPaid.value)
const change = computed(() => Math.max(0, totalPaid.value - props.grandTotal))

function addTender() {
  tenders.value.push({
    tender: 'cash',
    amount: remaining.value > 0 ? fmt(remaining.value) : zeroAmount.value,
    bank_account_id: null,
    reference: null,
  })
  activeIdx.value = tenders.value.length - 1
}

function removeTender(idx: number) {
  tenders.value.splice(idx, 1)
  if (activeIdx.value >= tenders.value.length) activeIdx.value = tenders.value.length - 1
}

function quickFill(amount: number, idx: number) {
  tenders.value[idx].amount = fmt(amount)
}

function tenderIcon(t: Tender['tender']) {
  return t === 'cash' ? Banknote : t === 'card' ? CreditCard : Building2
}

function confirm() {
  // Filter out zero-amount tenders before sending.
  const cleaned = tenders.value
    .map((t) => ({ ...t, amount: fmt(parseFloat(t.amount) || 0) }))
    .filter((t) => parseFloat(t.amount) > 0)
  emit('confirm', cleaned)
}

const canConfirm = computed(
  () => Math.abs(totalPaid.value - props.grandTotal) < settlementEpsilon.value || totalPaid.value > props.grandTotal,
)

const activeAmount = computed({
  get: () => tenders.value[activeIdx.value]?.amount ?? '',
  set: (v: string) => {
    if (tenders.value[activeIdx.value]) tenders.value[activeIdx.value].amount = v
  },
})
</script>

<template>
  <Dialog :open="open" @update:open="(v) => emit('update:open', v)">
    <DialogContent class="max-w-5xl sm:max-w-5xl lg:max-w-6xl">
      <DialogHeader>
        <DialogTitle class="text-[20px]">
          {{ __('Payment') }} · {{ currency.code }} {{ fmt(grandTotal) }}
        </DialogTitle>
      </DialogHeader>

      <div class="grid gap-6 md:grid-cols-[1.3fr_1fr]">
        <!-- Tenders list -->
        <div class="space-y-3">
          <div
            v-for="(t, idx) in tenders"
            :key="idx"
            class="rounded-sm border bg-paper-50 p-3 transition"
            :class="activeIdx === idx ? 'border-brand-500 ring-2 ring-brand-200' : 'border-ink-200'"
            @click="activeIdx = idx"
          >
            <div class="flex items-center gap-2">
              <component :is="tenderIcon(t.tender)" class="size-5 shrink-0 text-brand-600" />
              <Select :model-value="t.tender" @update:model-value="(v) => (t.tender = v as Tender['tender'])">
                <SelectTrigger class="h-12 w-40 shrink-0 text-[14px]">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="cash">{{ __('Cash') }}</SelectItem>
                  <SelectItem value="card">{{ __('Card') }}</SelectItem>
                  <SelectItem value="bank_transfer">{{ __('Bank Transfer') }}</SelectItem>
                </SelectContent>
              </Select>
              <Input
                v-model="t.amount"
                type="number"
                inputmode="decimal"
                :step="amountStep"
                min="0"
                class="h-12 min-w-0 flex-1 text-end text-[18px] font-semibold tabular-nums"
                @focus="activeIdx = idx"
              />
              <Button variant="ghost" size="icon" class="shrink-0" :disabled="tenders.length <= 1" @click.stop="removeTender(idx)">
                <Trash2 class="size-4 text-danger-500" />
              </Button>
            </div>

            <div v-if="t.tender !== 'cash'" class="mt-2 grid grid-cols-2 gap-2">
              <Select
                :model-value="t.bank_account_id ? String(t.bank_account_id) : ''"
                @update:model-value="(v) => (t.bank_account_id = v ? Number(v) : null)"
              >
                <SelectTrigger class="h-11 text-[14px]">
                  <SelectValue :placeholder="__('Bank account')" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="b in bankAccounts" :key="b.id" :value="String(b.id)">
                    {{ localized(b.name) }}
                  </SelectItem>
                </SelectContent>
              </Select>
              <Input
                v-model="t.reference"
                :placeholder="__('Reference / last 4')"
                class="h-11 text-[14px]"
              />
            </div>

            <div v-if="t.tender === 'cash'" class="mt-2 flex flex-wrap gap-1.5">
              <Button
                v-for="amount in [5, 10, 20, 50, 100, grandTotal]"
                :key="amount"
                type="button"
                variant="secondary"
                size="sm"
                class="h-10 px-3 text-[13px] tabular-nums"
                @click.stop="quickFill(Number(amount), idx)"
              >
                {{ fmt(Number(amount)) }}
              </Button>
            </div>
          </div>

          <Button variant="outline" class="h-11 w-full text-[14px]" @click="addTender">
            <Plus class="size-4" /> {{ __('Add tender') }}
          </Button>

          <div class="rounded-sm border border-ink-200 bg-white p-3 text-[14px]">
            <div class="flex justify-between">
              <span class="text-ink-600">{{ __('Total due') }}</span>
              <span class="font-semibold tabular-nums">{{ currency.code }} {{ fmt(grandTotal) }}</span>
            </div>
            <div class="mt-1 flex justify-between">
              <span class="text-ink-600">{{ __('Tendered') }}</span>
              <span class="font-semibold tabular-nums">{{ currency.code }} {{ fmt(totalPaid) }}</span>
            </div>
            <div v-if="remaining > 0" class="mt-1 flex justify-between text-danger-700">
              <span>{{ __('Remaining') }}</span>
              <span class="font-semibold tabular-nums">{{ currency.code }} {{ fmt(remaining) }}</span>
            </div>
            <div v-if="change > 0" class="mt-1 flex justify-between text-success-700">
              <span>{{ __('Change') }}</span>
              <span class="font-semibold tabular-nums">{{ currency.code }} {{ fmt(change) }}</span>
            </div>
          </div>
        </div>

        <!-- Numpad -->
        <div>
          <PosNumpad v-model="activeAmount" :decimals="decimals" show-clear />
        </div>
      </div>

      <DialogFooter>
        <Button variant="outline" class="h-11 px-6 text-[14px]" @click="emit('update:open', false)">{{ __('Cancel') }}</Button>
        <Button class="h-11 px-6 text-[15px]" :disabled="!canConfirm || processing" @click="confirm">
          {{ processing ? __('Processing…') : __('Confirm') }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
