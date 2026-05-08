<script setup lang="ts">
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { router, useHttp } from '@inertiajs/vue3'
import { Link } from '@inertiajs/vue3'
import PosLayout from '@/layouts/PosLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import PosCart from '@/components/darejer/pos/PosCart.vue'
import PosItemSearch from '@/components/darejer/pos/PosItemSearch.vue'
import PosPaymentDialog from '@/components/darejer/pos/PosPaymentDialog.vue'
import PosSessionOpenForm from '@/components/darejer/pos/PosSessionOpenForm.vue'
import PosCustomerPicker from '@/components/darejer/pos/PosCustomerPicker.vue'
import useTranslation from '@/composables/useTranslation'
import { toast } from 'vue-sonner'
import { Calculator, LayoutDashboard, ListChecks, LogOut, Receipt, RotateCcw, User, X } from 'lucide-vue-next'

defineOptions({ layout: PosLayout })

const { __, resolveTranslatable: localized } = useTranslation()
const success = (msg: string) => toast.success(msg)
const error = (msg: string) => toast.error(msg)

interface PosItem {
  id: number
  code: string
  barcode: string | null
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

interface Customer {
  id: number
  code: string
  name: Record<string, string> | string
}

interface Session {
  id: number
  voucher_no: string
  cashier_user_id: number
  cashbox_id: number
  warehouse_id: number | null
  customer_account_id: number | null
  currency_id: number
  opening_cash: string
  total_sales: string
  invoice_count: number
  // Eloquent's $snakeAttributes (default true) snake-cases relation keys in toArray.
  cashbox: { id: number; code: string; name: Record<string, string> | string }
  warehouse: { id: number; code: string; name: Record<string, string> | string } | null
  customer_account: Customer | null
  currency: { id: number; code: string; symbol: string | null; minor_units: number }
}

const props = defineProps<{
  title: string
  breadcrumbs: Array<{ label: string; url?: string }>
  session: Session | null
  bootstrap: {
    cashboxes: Array<{ id: number; code: string; name: string; currency_id: number }>
    warehouses: Array<{ id: number; code: string; name: string }>
    currencies: Array<{ id: number; code: string; symbol: string | null; minor_units: number }>
    bank_accounts: Array<{ id: number; code: string; name: string }>
    walk_in_customer: Customer | null
    default_currency_id: number | null
  }
  urls: {
    open_session: string
    close_session_template: string
    item_search: string
    customer_search: string
    customer_quick_add: string
    checkout: string
    receipt_template: string
    sessions_index: string
  }
}>()

const cart = ref<CartLine[]>([])
const customer = ref<Customer | null>(props.session?.customer_account ?? props.bootstrap.walk_in_customer)
const paymentOpen = ref(false)
const customerOpen = ref(false)

const isWalkIn = computed(
  () => !!customer.value && !!props.bootstrap.walk_in_customer && customer.value.id === props.bootstrap.walk_in_customer.id,
)

function resetCustomer() {
  customer.value = props.bootstrap.walk_in_customer ?? null
}

watch(
  () => props.session?.customer_account,
  (c) => {
    if (c) customer.value = c
  },
)

// F2 opens the picker, Esc on the page (when no dialog is open) resets to
// the walk-in customer — supermarket-cashier muscle-memory.
function onKeydown(e: KeyboardEvent) {
  if (!props.session) return
  if (e.key === 'F2') {
    e.preventDefault()
    customerOpen.value = true
  }
}
onMounted(() => window.addEventListener('keydown', onKeydown))
onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown))

function lineTotal(line: CartLine): number {
  const qty = parseFloat(line.qty) || 0
  const rate = parseFloat(line.rate) || 0
  const disc = parseFloat(line.discount_pct) || 0
  return qty * rate * (1 - disc / 100)
}

const subtotal = computed(() =>
  cart.value.reduce((sum, l) => sum + lineTotal(l), 0),
)

// Tax + grand total are computed server-side at checkout. The cart shows a
// pre-tax subtotal so the cashier sees the running tally without a roundtrip
// per item; the payment dialog shows the authoritative grand total returned
// from the checkout endpoint.
const grandTotal = computed(() => subtotal.value)

const currencyDecimals = computed(() => props.session?.currency.minor_units ?? 2)
const moneyFormatter = computed(
  () =>
    new Intl.NumberFormat(undefined, {
      minimumFractionDigits: currencyDecimals.value,
      maximumFractionDigits: currencyDecimals.value,
    }),
)
function fmtMoney(n: number): string {
  return moneyFormatter.value.format(n)
}

function addToCart(item: PosItem) {
  const existing = cart.value.find((l) => l.item.id === item.id)
  if (existing) {
    existing.qty = String(parseFloat(existing.qty) + 1)
    return
  }
  // Normalize the seeded rate to the currency's minor_units. Items are stored
  // with full DB precision (e.g. "1200000.00"); for IQD we want "1200000" so
  // the input doesn't carry a fractional part that would never apply.
  const seeded = item.selling_price ? Number(item.selling_price) : 0
  cart.value.push({
    item,
    qty: '1',
    rate: Number.isFinite(seeded) ? seeded.toFixed(currencyDecimals.value) : (0).toFixed(currencyDecimals.value),
    discount_pct: '0',
  })
}

function clearCart() {
  cart.value = []
}

function openPayment() {
  if (!cart.value.length) {
    error(__('Cart is empty.'))
    return
  }
  if (!customer.value) {
    customerOpen.value = true
    return
  }
  paymentOpen.value = true
}

// useHttp owns the wire-level state. Field assignments on the bag are
// what get sent over the wire — no `data` option on `.post()` exists.
// Cast to a loose record because checkout's payload contains nested arrays
// of mixed scalars that don't satisfy the `FormDataType` shape.
// eslint-disable-next-line @typescript-eslint/no-explicit-any
const checkoutHttp = useHttp({
  customer_account_id: null,
  lines: [],
  tenders: [],
  notes: null,
} as any) as unknown as Record<string, unknown> & {
  processing: boolean
  post(url: string, opts?: object): Promise<unknown>
}

function handleCheckout(tenders: Array<{ tender: string; amount: string; bank_account_id?: number | null; reference?: string | null }>) {
  checkoutHttp.customer_account_id = customer.value?.id ?? null
  checkoutHttp.lines = cart.value.map((l) => ({
    item_id: l.item.id,
    qty: l.qty,
    rate: l.rate,
    discount_pct: l.discount_pct,
    warehouse_id: l.item.default_warehouse_id ?? props.session?.warehouse_id ?? null,
    uom_id: l.item.sales_uom_id ?? null,
    tax_code_id: l.item.default_tax_code_sales_id ?? null,
  }))
  checkoutHttp.tenders = tenders

  checkoutHttp.post(props.urls.checkout, {
    onSuccess: (response: unknown) => {
      const r = response as { message?: string; data?: { receipt_url?: string } }
      success(r?.message ?? __('Sale completed.'))
      paymentOpen.value = false
      clearCart()
      if (r?.data?.receipt_url) {
        window.open(r.data.receipt_url, '_blank', 'width=420,height=720')
      }
    },
    onError: (errors: Record<string, string>) => {
      const first = Object.values(errors)[0]
      if (first) error(first)
    },
  })
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
const closeHttp = useHttp({ counted_cash: '0' } as any) as unknown as Record<string, unknown> & {
  processing: boolean
  post(url: string, opts?: object): Promise<unknown>
}
const closeCash = ref('')
const closeOpen = ref(false)

function submitClose() {
  if (!props.session) return
  closeHttp.counted_cash = closeCash.value || '0'
  closeHttp.post(props.urls.close_session_template.replace('__ID__', String(props.session.id)), {
    onSuccess: (response: unknown) => {
      const r = response as { message?: string; redirect?: string }
      success(r?.message ?? __('Session closed.'))
      closeOpen.value = false
      if (r?.redirect) router.visit(r.redirect)
    },
    onError: (errors: Record<string, string>) => {
      const first = Object.values(errors)[0]
      if (first) error(first)
    },
  })
}

</script>

<template>
  <div class="flex h-full flex-col">
    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-ink-200 bg-white px-3 py-2 sm:px-4">
      <div class="flex min-w-0 items-center gap-2">
        <Calculator class="size-6 text-brand-600" />
        <span class="text-[15px] font-semibold text-ink-900">{{ __('Point of Sale') }}</span>
        <span v-if="session" class="ms-1 rounded-sm bg-success-50 px-2 py-1 text-[12px] font-medium text-success-700">
          {{ session.voucher_no }}
        </span>
        <span v-if="session" class="hidden truncate text-[13px] text-ink-500 sm:inline">
          · {{ __('Cashbox') }}: {{ localized(session.cashbox.name) }}
        </span>
      </div>
      <div class="flex items-center gap-2">
        <Link :href="urls.sessions_index">
          <Button variant="outline" class="h-10 border-paper-300 px-3 text-[14px]">
            <ListChecks class="size-4" />
            <span class="hidden sm:inline">{{ __('POS Sessions') }}</span>
          </Button>
        </Link>
        <Link href="/darejer">
          <Button variant="outline" class="h-10 border-paper-300 px-3 text-[14px]">
            <LayoutDashboard class="size-4" />
            <span class="hidden sm:inline">{{ __('Dashboard') }}</span>
          </Button>
        </Link>
        <Button v-if="session" variant="outline" class="h-10 border-paper-300 px-3 text-[14px]" @click="closeOpen = true">
          <LogOut class="size-4" />
          <span class="hidden sm:inline">{{ __('Close session') }}</span>
        </Button>
      </div>
    </div>

    <PosSessionOpenForm
      v-if="!session"
      :bootstrap="bootstrap"
      :open-url="urls.open_session"
    />

    <div v-else class="grid min-h-0 flex-1 grid-cols-1 gap-3 overflow-hidden bg-paper-100 p-3 md:grid-cols-12">
      <!-- Cart panel (left on tablet+, top on phones) -->
      <div class="flex min-h-0 flex-col gap-3 md:col-span-5 lg:col-span-5 xl:col-span-4">
        <div class="flex items-center justify-between rounded-sm border border-ink-200 bg-white p-3">
          <button
            type="button"
            class="flex min-w-0 items-center gap-2 text-start"
            @click="customerOpen = true"
          >
            <span
              class="flex size-9 shrink-0 items-center justify-center rounded-full"
              :class="isWalkIn ? 'bg-paper-150 text-ink-600' : 'bg-brand-50 text-brand-700'"
            >
              <User class="size-4" />
            </span>
            <span class="min-w-0">
              <span class="block text-[11px] uppercase tracking-wider text-ink-500">
                {{ __('Customer') }}
                <span class="ms-1 rounded-sm bg-paper-100 px-1 py-0.5 font-mono text-[10px] tracking-normal text-ink-500">F2</span>
              </span>
              <span class="block truncate text-[15px] font-semibold text-ink-900 hover:text-brand-600">
                {{ customer ? localized(customer.name) : __('Choose customer') }}
              </span>
            </span>
          </button>
          <div class="flex items-center gap-1">
            <Button
              v-if="customer && !isWalkIn && bootstrap.walk_in_customer"
              variant="ghost"
              class="h-10 px-2 text-[13px]"
              :title="__('Reset to :name', { name: localized(bootstrap.walk_in_customer.name) })"
              @click="resetCustomer"
            >
              <RotateCcw class="size-4" />
            </Button>
            <Button variant="ghost" class="h-10 px-3 text-[13px]" :disabled="!cart.length" @click="clearCart">
              <X class="size-4" /> {{ __('Clear') }}
            </Button>
          </div>
        </div>

        <PosCart
          v-model:cart="cart"
          :currency="session.currency"
          class="flex-1 min-h-0 overflow-hidden"
        />

        <div class="rounded-sm border border-ink-200 bg-white p-4">
          <div class="mb-3 flex items-center justify-between text-[14px] text-ink-700">
            <span>{{ __('Subtotal (pre-tax)') }}</span>
            <span class="font-semibold tabular-nums">{{ session.currency.code }} {{ fmtMoney(subtotal) }}</span>
          </div>
          <Button class="h-14 w-full text-[16px] font-bold" :disabled="!cart.length" @click="openPayment">
            <Receipt class="size-5" /> {{ __('Pay') }} · {{ session.currency.code }} {{ fmtMoney(grandTotal) }}
          </Button>
        </div>
      </div>

      <!-- Item search panel (right on tablet+, bottom on phones) -->
      <div class="min-h-0 md:col-span-7 lg:col-span-7 xl:col-span-8">
        <PosItemSearch :search-url="urls.item_search" :currency="session.currency" @select="addToCart" />
      </div>
    </div>

    <PosPaymentDialog
      v-if="session"
      v-model:open="paymentOpen"
      :grand-total="grandTotal"
      :currency="session.currency"
      :bank-accounts="bootstrap.bank_accounts"
      :processing="checkoutHttp.processing"
      @confirm="handleCheckout"
    />

    <PosCustomerPicker
      v-model:open="customerOpen"
      :search-url="urls.customer_search"
      :walk-in-customer="bootstrap.walk_in_customer"
      :quick-add-url="urls.customer_quick_add"
      @select="(c) => { customer = c }"
    />

    <!-- Close-session dialog (inline so we don't need yet another file) -->
    <div v-if="closeOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
      <div class="w-full max-w-md rounded-sm bg-white p-5 shadow-lg sm:p-6">
        <div class="mb-4 text-[16px] font-semibold">{{ __('Close POS Session') }}</div>
        <div v-if="session" class="mb-4 space-y-1.5 rounded-sm bg-paper-50 p-3 text-[14px] text-ink-700">
          <div class="flex justify-between"><span>{{ __('Opening cash') }}</span><span class="tabular-nums">{{ fmtMoney(Number(session.opening_cash)) }}</span></div>
          <div class="flex justify-between"><span>{{ __('Sales (count)') }}</span><span class="tabular-nums">{{ session.invoice_count }}</span></div>
          <div class="flex justify-between"><span>{{ __('Total sales') }}</span><span class="tabular-nums">{{ fmtMoney(Number(session.total_sales)) }}</span></div>
        </div>
        <label class="mb-1.5 block text-[13px] font-medium text-ink-700">{{ __('Counted cash') }}</label>
        <Input
          v-model="closeCash"
          type="number"
          inputmode="decimal"
          step="0.01"
          class="h-12 text-end text-[16px] tabular-nums"
        />
        <div class="mt-5 flex justify-end gap-2">
          <Button variant="outline" class="h-11 px-5 text-[14px]" @click="closeOpen = false">{{ __('Cancel') }}</Button>
          <Button class="h-11 px-5 text-[14px]" :disabled="closeHttp.processing" @click="submitClose">
            {{ closeHttp.processing ? __('Closing…') : __('Confirm close') }}
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>
