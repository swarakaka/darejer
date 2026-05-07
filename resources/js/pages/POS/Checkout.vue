<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { router, useHttp } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import AppBreadcrumbs from '@/components/darejer/AppBreadcrumbs.vue'
import PosCart from '@/components/darejer/pos/PosCart.vue'
import PosItemSearch from '@/components/darejer/pos/PosItemSearch.vue'
import PosPaymentDialog from '@/components/darejer/pos/PosPaymentDialog.vue'
import PosSessionOpenForm from '@/components/darejer/pos/PosSessionOpenForm.vue'
import PosCustomerPicker from '@/components/darejer/pos/PosCustomerPicker.vue'
import useTranslation from '@/composables/useTranslation'
import { toast } from 'vue-sonner'
import { Calculator, LogOut, Receipt, X } from 'lucide-vue-next'

defineOptions({ layout: AppLayout })

const { __ } = useTranslation()
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
  currency: { id: number; code: string; symbol: string | null }
}

const props = defineProps<{
  title: string
  breadcrumbs: Array<{ label: string; url?: string }>
  session: Session | null
  bootstrap: {
    cashboxes: Array<{ id: number; code: string; name: string; currency_id: number }>
    warehouses: Array<{ id: number; code: string; name: string }>
    currencies: Array<{ id: number; code: string; symbol: string | null }>
    bank_accounts: Array<{ id: number; code: string; name: string }>
    walk_in_customer: Customer | null
    default_currency_id: number | null
  }
  urls: {
    open_session: string
    close_session_template: string
    item_search: string
    customer_search: string
    checkout: string
    receipt_template: string
    sessions_index: string
  }
}>()

const cart = ref<CartLine[]>([])
const customer = ref<Customer | null>(props.session?.customer_account ?? props.bootstrap.walk_in_customer)
const paymentOpen = ref(false)
const customerOpen = ref(false)

watch(
  () => props.session?.customer_account,
  (c) => {
    if (c) customer.value = c
  },
)

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

function addToCart(item: PosItem) {
  const existing = cart.value.find((l) => l.item.id === item.id)
  if (existing) {
    existing.qty = String(parseFloat(existing.qty) + 1)
    return
  }
  cart.value.push({
    item,
    qty: '1',
    rate: item.selling_price ?? '0',
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
    <div class="flex items-center justify-between border-b border-ink-200 bg-white px-4 py-2">
      <div class="flex items-center gap-3">
        <Calculator class="size-5 text-brand-600" />
        <AppBreadcrumbs />
      </div>
      <div v-if="session" class="flex items-center gap-3 text-[13px] text-ink-700">
        <span class="rounded-sm bg-success-50 px-2 py-0.5 text-success-700">{{ __('Session') }} {{ session.voucher_no }}</span>
        <span>{{ __('Cashbox') }}: {{ typeof session.cashbox.name === 'object' ? Object.values(session.cashbox.name)[0] : session.cashbox.name }}</span>
        <Button variant="outline" size="sm" @click="closeOpen = true">
          <LogOut class="size-3.5" /> {{ __('Close session') }}
        </Button>
      </div>
    </div>

    <PosSessionOpenForm
      v-if="!session"
      :bootstrap="bootstrap"
      :open-url="urls.open_session"
    />

    <div v-else class="grid min-h-0 flex-1 grid-cols-12 gap-3 overflow-hidden bg-paper-100 p-3">
      <!-- Cart panel (left) -->
      <div class="col-span-12 flex flex-col gap-3 lg:col-span-5">
        <div class="flex items-center justify-between rounded-sm border border-ink-200 bg-white p-3">
          <div>
            <div class="text-[11px] uppercase tracking-wider text-ink-500">{{ __('Customer') }}</div>
            <button
              type="button"
              class="text-[14px] font-semibold text-ink-900 hover:text-brand-600"
              @click="customerOpen = true"
            >
              {{ customer ? (typeof customer.name === 'object' ? Object.values(customer.name)[0] : customer.name) : __('Choose customer') }}
            </button>
          </div>
          <Button variant="ghost" size="sm" :disabled="!cart.length" @click="clearCart">
            <X class="size-3.5" /> {{ __('Clear') }}
          </Button>
        </div>

        <PosCart
          v-model:cart="cart"
          :currency="session.currency"
          class="flex-1 overflow-hidden"
        />

        <div class="rounded-sm border border-ink-200 bg-white p-4">
          <div class="mb-3 flex items-center justify-between text-[14px] text-ink-700">
            <span>{{ __('Subtotal (pre-tax)') }}</span>
            <span class="font-semibold">{{ session.currency.code }} {{ subtotal.toFixed(2) }}</span>
          </div>
          <Button class="h-12 w-full text-[15px]" :disabled="!cart.length" @click="openPayment">
            <Receipt class="size-4" /> {{ __('Pay') }} · {{ session.currency.code }} {{ grandTotal.toFixed(2) }}
          </Button>
        </div>
      </div>

      <!-- Item search panel (right) -->
      <div class="col-span-12 min-h-0 lg:col-span-7">
        <PosItemSearch :search-url="urls.item_search" @select="addToCart" />
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
      @select="(c) => { customer = c; customerOpen = false }"
    />

    <!-- Close-session dialog (inline so we don't need yet another file) -->
    <div v-if="closeOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
      <div class="w-[420px] rounded-sm bg-white p-5 shadow-lg">
        <div class="mb-4 text-[15px] font-semibold">{{ __('Close POS Session') }}</div>
        <div v-if="session" class="mb-3 space-y-1 text-[13px] text-ink-700">
          <div class="flex justify-between"><span>{{ __('Opening cash') }}</span><span>{{ session.opening_cash }}</span></div>
          <div class="flex justify-between"><span>{{ __('Sales (count)') }}</span><span>{{ session.invoice_count }}</span></div>
          <div class="flex justify-between"><span>{{ __('Total sales') }}</span><span>{{ session.total_sales }}</span></div>
        </div>
        <label class="mb-1 block text-[12px] font-medium text-ink-700">{{ __('Counted cash') }}</label>
        <Input v-model="closeCash" type="number" step="0.01" />
        <div class="mt-4 flex justify-end gap-2">
          <Button variant="outline" size="sm" @click="closeOpen = false">{{ __('Cancel') }}</Button>
          <Button size="sm" :disabled="closeHttp.processing" @click="submitClose">
            {{ closeHttp.processing ? __('Closing…') : __('Confirm close') }}
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>
