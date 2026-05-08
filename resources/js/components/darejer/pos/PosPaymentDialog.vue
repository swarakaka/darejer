<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
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
import { Plus, Trash2, Banknote, CreditCard, Building2, Wallet } from 'lucide-vue-next'
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

const moneyFormatter = computed(
  () =>
    new Intl.NumberFormat(undefined, {
      minimumFractionDigits: decimals.value,
      maximumFractionDigits: decimals.value,
    }),
)
// Display formatting (locale-aware, with thousands separator) — distinct from
// the wire format used to seed `t.amount`, which stays toFixed-based so the
// number input doesn't choke on grouping characters.
function fmt(n: number): string {
  return moneyFormatter.value.format(n)
}
function rawFixed(n: number): string {
  return n.toFixed(decimals.value)
}

watch(
  () => props.open,
  (open) => {
    if (open) {
      tenders.value = [{ tender: 'cash', amount: rawFixed(props.grandTotal), bank_account_id: null, reference: null }]
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
    amount: remaining.value > 0 ? rawFixed(remaining.value) : zeroAmount.value,
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
  tenders.value[idx].amount = rawFixed(amount)
}

function tenderIcon(t: Tender['tender']) {
  return t === 'cash' ? Banknote : t === 'card' ? CreditCard : Building2
}

function tenderLabel(t: Tender['tender']): string {
  return t === 'cash' ? __('Cash') : t === 'card' ? __('Card') : __('Bank Transfer')
}

function confirm() {
  // Filter out zero-amount tenders before sending. Wire format stays
  // dot-decimal so the backend parses cleanly regardless of locale.
  const cleaned = tenders.value
    .map((t) => ({ ...t, amount: rawFixed(parseFloat(t.amount) || 0) }))
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

// Denomination chips drop the grand-total entry when the cart total is itself
// a round denomination, so we don't render a duplicate "100 / 100" pair.
const quickAmounts = computed(() => {
  const base = [5, 10, 20, 50, 100]
  return base.includes(props.grandTotal) ? base : [...base, props.grandTotal]
})
</script>

<template>
  <Dialog :open="open" @update:open="(v) => emit('update:open', v)">
    <DialogContent
      class="max-w-5xl gap-0 overflow-hidden p-0 sm:max-w-5xl lg:max-w-6xl [&>button.absolute]:end-4 [&>button.absolute]:top-4 [&>button.absolute]:size-9 [&>button.absolute>svg]:size-5"
      @pointer-down-outside="(e) => e.preventDefault()"
      @interact-outside="(e) => e.preventDefault()"
      @escape-key-down="(e) => e.preventDefault()"
    >
      <DialogHeader class="space-y-0">
        <!-- Hero header: small label on the start, big total on the end -->
        <div class="flex items-center justify-between gap-6 border-b border-ink-200 bg-paper-75 px-6 py-4 pe-16">
          <div class="min-w-0">
            <DialogTitle class="flex items-center gap-2 text-[12px] font-semibold uppercase tracking-[0.08em] text-ink-500">
              <Wallet class="size-4 text-brand-600" />
              {{ __('Payment') }}
            </DialogTitle>
            <p class="mt-0.5 truncate text-[14px] text-ink-700">
              {{ __('Settle the open balance to finalise this sale.') }}
            </p>
          </div>
          <div class="text-end">
            <div class="text-[11px] font-semibold uppercase tracking-[0.08em] text-ink-500">
              {{ __('Total due') }}
            </div>
            <div class="leading-tight tabular-nums text-ink-900">
              <span class="text-[26px] font-bold">{{ fmt(grandTotal) }}</span>
              <span class="ms-1 text-[14px] font-semibold text-ink-500">{{ currency.code }}</span>
            </div>
          </div>
        </div>
      </DialogHeader>

      <!-- Body -->
      <div class="grid gap-6 px-6 py-5 md:grid-cols-[1.3fr_1fr]">
        <!-- Tenders list -->
        <div class="flex min-w-0 flex-col gap-3">
          <div class="text-[11px] font-semibold uppercase tracking-[0.08em] text-ink-500">
            {{ __('Payment Methods') }}
          </div>

          <div
            v-for="(t, idx) in tenders"
            :key="idx"
            class="relative cursor-pointer overflow-hidden rounded-sm border bg-white transition-[border-color,box-shadow]"
            :class="
              activeIdx === idx
                ? 'border-brand-500 shadow-[0_1.6px_3.6px_rgba(0,0,0,0.13),_0_0.3px_0.9px_rgba(0,0,0,0.1)]'
                : 'border-ink-200 hover:border-ink-300'
            "
            @click="activeIdx = idx"
          >
            <!-- Active accent stripe (logical-direction so it flips under RTL) -->
            <div
              v-if="activeIdx === idx"
              class="absolute inset-y-0 start-0 w-[3px] bg-brand-500"
              aria-hidden="true"
            />

            <div class="flex items-center justify-between gap-2 border-b border-paper-150 px-3 py-2 ps-4">
              <div class="flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.06em]">
                <span
                  class="inline-flex h-5 w-5 items-center justify-center rounded-sm"
                  :class="activeIdx === idx ? 'bg-brand-500 text-white' : 'bg-paper-150 text-ink-600'"
                >
                  {{ idx + 1 }}
                </span>
                <span class="text-ink-600">{{ tenderLabel(t.tender) }}</span>
              </div>
              <Button
                variant="ghost"
                size="icon"
                class="size-8 shrink-0"
                :disabled="tenders.length <= 1"
                @click.stop="removeTender(idx)"
              >
                <Trash2 class="size-4 text-danger-500" />
              </Button>
            </div>

            <div class="space-y-2.5 p-3 ps-4">
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
              </div>

              <div v-if="t.tender !== 'cash'" class="grid grid-cols-2 gap-2">
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

              <div v-if="t.tender === 'cash'" class="flex flex-wrap gap-1.5">
                <button
                  v-for="amount in quickAmounts"
                  :key="amount"
                  type="button"
                  class="h-9 rounded-sm border border-ink-200 bg-white px-3 text-[12.5px] font-semibold tabular-nums text-ink-700 transition-colors hover:border-brand-400 hover:bg-brand-50 hover:text-brand-700 active:bg-brand-100"
                  @click.stop="quickFill(Number(amount), idx)"
                >
                  {{ fmt(Number(amount)) }}
                </button>
              </div>
            </div>
          </div>

          <Button
            variant="outline"
            class="h-11 w-full border-dashed text-[14px] text-ink-600 hover:text-ink-900"
            @click="addTender"
          >
            <Plus class="size-4" /> {{ __('Add tender') }}
          </Button>

          <!-- Summary -->
          <div class="overflow-hidden rounded-sm border border-ink-200 bg-paper-75">
            <dl class="divide-y divide-paper-200 text-[13px]">
              <div class="flex items-center justify-between px-3 py-2">
                <dt class="text-ink-600">{{ __('Total due') }}</dt>
                <dd class="font-semibold tabular-nums text-ink-900">
                  {{ currency.code }} {{ fmt(grandTotal) }}
                </dd>
              </div>
              <div class="flex items-center justify-between px-3 py-2">
                <dt class="text-ink-600">{{ __('Tendered') }}</dt>
                <dd class="font-semibold tabular-nums text-ink-900">
                  {{ currency.code }} {{ fmt(totalPaid) }}
                </dd>
              </div>
              <div
                v-if="remaining > 0"
                class="flex items-center justify-between bg-danger-50 px-3 py-2.5 text-danger-700"
              >
                <dt class="text-[12px] font-semibold uppercase tracking-[0.06em]">{{ __('Remaining') }}</dt>
                <dd class="text-[15px] font-bold tabular-nums">
                  {{ currency.code }} {{ fmt(remaining) }}
                </dd>
              </div>
              <div
                v-if="change > 0"
                class="flex items-center justify-between bg-success-50 px-3 py-2.5 text-success-700"
              >
                <dt class="text-[12px] font-semibold uppercase tracking-[0.06em]">{{ __('Change') }}</dt>
                <dd class="text-[15px] font-bold tabular-nums">
                  {{ currency.code }} {{ fmt(change) }}
                </dd>
              </div>
            </dl>
          </div>
        </div>

        <!-- Numpad -->
        <div class="flex flex-col gap-3">
          <div class="text-[11px] font-semibold uppercase tracking-[0.08em] text-ink-500">
            {{ __('Keypad') }}
          </div>
          <div class="rounded-sm border border-ink-200 bg-paper-75 p-3">
            <PosNumpad v-model="activeAmount" :decimals="decimals" show-clear />
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="flex items-center justify-between gap-3 border-t border-ink-200 bg-paper-75 px-6 py-3">
        <div class="text-[12px] text-ink-500">
          <span v-if="remaining > 0">{{ __('Outstanding amount must be settled to confirm.') }}</span>
          <span v-else-if="change > 0">{{ __('Overpayment will be returned as change.') }}</span>
          <span v-else>{{ __('Balance settled — ready to confirm.') }}</span>
        </div>
        <div class="flex items-center gap-2">
          <Button
            variant="outline"
            class="h-11 px-6 text-[14px]"
            @click="emit('update:open', false)"
          >
            {{ __('Cancel') }}
          </Button>
          <Button
            class="h-11 min-w-[14rem] justify-between px-5 text-[15px]"
            :disabled="!canConfirm || processing"
            @click="confirm"
          >
            <span>{{ processing ? __('Processing…') : __('Confirm Payment') }}</span>
            <span class="ms-3 tabular-nums opacity-90">
              {{ currency.code }} {{ fmt(processing ? totalPaid : Math.max(grandTotal, totalPaid)) }}
            </span>
          </Button>
        </div>
      </div>
    </DialogContent>
  </Dialog>
</template>
