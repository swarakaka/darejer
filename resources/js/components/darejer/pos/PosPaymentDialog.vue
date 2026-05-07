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
import { Plus, Trash2, Banknote, CreditCard, Building2 } from 'lucide-vue-next'
import useTranslation from '@/composables/useTranslation'

interface Tender {
  tender: 'cash' | 'card' | 'bank_transfer'
  amount: string
  bank_account_id: number | null
  reference: string | null
}

const props = defineProps<{
  open: boolean
  grandTotal: number
  currency: { code: string; symbol: string | null }
  bankAccounts: Array<{ id: number; code: string; name: string }>
  processing: boolean
}>()

const emit = defineEmits<{
  'update:open': [value: boolean]
  confirm: [tenders: Tender[]]
}>()

const { __ } = useTranslation()

const tenders = ref<Tender[]>([])

watch(
  () => props.open,
  (open) => {
    if (open) {
      tenders.value = [{ tender: 'cash', amount: props.grandTotal.toFixed(2), bank_account_id: null, reference: null }]
    }
  },
)

const totalPaid = computed(() => tenders.value.reduce((s, t) => s + (parseFloat(t.amount) || 0), 0))
const remaining = computed(() => props.grandTotal - totalPaid.value)
const change = computed(() => Math.max(0, totalPaid.value - props.grandTotal))

function addTender() {
  tenders.value.push({
    tender: 'cash',
    amount: remaining.value > 0 ? remaining.value.toFixed(2) : '0.00',
    bank_account_id: null,
    reference: null,
  })
}

function removeTender(idx: number) {
  tenders.value.splice(idx, 1)
}

function quickFill(amount: number, idx: number) {
  tenders.value[idx].amount = amount.toFixed(2)
}

function tenderIcon(t: Tender['tender']) {
  return t === 'cash' ? Banknote : t === 'card' ? CreditCard : Building2
}

function confirm() {
  // Filter out zero-amount tenders before sending.
  const cleaned = tenders.value
    .map((t) => ({ ...t, amount: (parseFloat(t.amount) || 0).toFixed(2) }))
    .filter((t) => parseFloat(t.amount) > 0)
  emit('confirm', cleaned)
}

const canConfirm = computed(
  () => Math.abs(totalPaid.value - props.grandTotal) < 0.01 || totalPaid.value > props.grandTotal,
)
</script>

<template>
  <Dialog :open="open" @update:open="(v) => emit('update:open', v)">
    <DialogContent class="max-w-lg">
      <DialogHeader>
        <DialogTitle>{{ __('Payment') }} · {{ currency.code }} {{ grandTotal.toFixed(2) }}</DialogTitle>
      </DialogHeader>

      <div class="space-y-3">
        <div
          v-for="(t, idx) in tenders"
          :key="idx"
          class="rounded-sm border border-ink-200 bg-paper-50 p-3"
        >
          <div class="flex items-center gap-2">
            <component :is="tenderIcon(t.tender)" class="size-4 text-brand-600" />
            <Select :model-value="t.tender" @update:model-value="(v) => (t.tender = v as Tender['tender'])">
              <SelectTrigger class="h-8 w-40 text-[13px]">
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
              step="0.01"
              min="0"
              class="h-8 flex-1 text-end"
            />
            <Button variant="ghost" size="icon-sm" :disabled="tenders.length <= 1" @click="removeTender(idx)">
              <Trash2 class="size-3.5 text-danger-500" />
            </Button>
          </div>

          <div v-if="t.tender !== 'cash'" class="mt-2 grid grid-cols-2 gap-2">
            <Select
              :model-value="t.bank_account_id ? String(t.bank_account_id) : ''"
              @update:model-value="(v) => (t.bank_account_id = v ? Number(v) : null)"
            >
              <SelectTrigger class="h-8 text-[13px]">
                <SelectValue :placeholder="__('Bank account')" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="b in bankAccounts" :key="b.id" :value="String(b.id)">
                  {{ b.name }}
                </SelectItem>
              </SelectContent>
            </Select>
            <Input
              v-model="t.reference"
              :placeholder="__('Reference / last 4')"
              class="h-8 text-[13px]"
            />
          </div>

          <div v-if="t.tender === 'cash'" class="mt-2 flex flex-wrap gap-1">
            <Button
              v-for="amount in [5, 10, 20, 50, 100, grandTotal]"
              :key="amount"
              type="button"
              variant="secondary"
              size="sm"
              class="h-7 px-2 text-[12px]"
              @click="quickFill(Number(amount), idx)"
            >
              {{ Number(amount).toFixed(2) }}
            </Button>
          </div>
        </div>

        <Button variant="outline" size="sm" class="w-full" @click="addTender">
          <Plus class="size-3.5" /> {{ __('Add tender') }}
        </Button>

        <div class="rounded-sm border border-ink-200 bg-white p-3 text-[13px]">
          <div class="flex justify-between">
            <span class="text-ink-600">{{ __('Total due') }}</span>
            <span class="font-semibold">{{ currency.code }} {{ grandTotal.toFixed(2) }}</span>
          </div>
          <div class="mt-1 flex justify-between">
            <span class="text-ink-600">{{ __('Tendered') }}</span>
            <span class="font-semibold">{{ currency.code }} {{ totalPaid.toFixed(2) }}</span>
          </div>
          <div v-if="remaining > 0" class="mt-1 flex justify-between text-danger-700">
            <span>{{ __('Remaining') }}</span>
            <span class="font-semibold">{{ currency.code }} {{ remaining.toFixed(2) }}</span>
          </div>
          <div v-if="change > 0" class="mt-1 flex justify-between text-success-700">
            <span>{{ __('Change') }}</span>
            <span class="font-semibold">{{ currency.code }} {{ change.toFixed(2) }}</span>
          </div>
        </div>
      </div>

      <DialogFooter>
        <Button variant="outline" size="sm" @click="emit('update:open', false)">{{ __('Cancel') }}</Button>
        <Button :disabled="!canConfirm || processing" @click="confirm">
          {{ processing ? __('Processing…') : __('Confirm') }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
