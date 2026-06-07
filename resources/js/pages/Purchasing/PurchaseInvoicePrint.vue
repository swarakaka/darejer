<script setup lang="ts">
import { computed, onMounted } from 'vue'
import useTranslation from '@/composables/useTranslation'

type Translatable = Record<string, string> | string | null

interface InvoiceLine {
  id: number
  qty: string
  rate: string
  amount: string
  discount_pct: string | null
  discount_amount: string | null
  tax_amount: string | null
  item: { code: string; name: Translatable } | null
  uom: { name: Translatable; decimals: number | null } | null
  warehouse: { name: Translatable } | null
  tax_code: { code: string; rate: string | null } | null
}

interface Invoice {
  voucher_no: string
  voucher_date: string
  due_date: string | null
  fx_rate: string
  total_qty: string
  total_amount: string
  total_discount: string
  total_tax: string
  grand_total: string
  paid_amount: string
  outstanding: string
  notes: Translatable
  status: string
  posting_status: string
  payment_status: string
  company: {
    code: string
    name: Translatable
    legal_name: Translatable
    address: Translatable
    tax_id: string | null
    mobile: string | null
    email: string | null
  } | null
  branch: { code: string; name: Translatable } | null
  department: { code: string; name: Translatable } | null
  supplier_account: {
    code: string
    name: Translatable
    legal_name: Translatable
    tax_id: string | null
    vat_id: string | null
    email: string | null
    mobile: string | null
  } | null
  purchase_order: { voucher_no: string } | null
  goods_receipt: { voucher_no: string } | null
  currency: { code: string; symbol: string | null; minor_units: number } | null
  buyer: { name: string } | null
  lines: InvoiceLine[]
}

const props = defineProps<{ invoice: Invoice }>()
const { __, resolveTranslatable: localized } = useTranslation()

const decimals = computed(() => props.invoice.currency?.minor_units ?? 2)
const moneyFormatter = computed(
  () =>
    new Intl.NumberFormat(undefined, {
      minimumFractionDigits: decimals.value,
      maximumFractionDigits: decimals.value,
    }),
)
function fmt(value: string | number | null | undefined): string {
  if (value === null || value === undefined || value === '') {
    return moneyFormatter.value.format(0)
  }
  const n = Number(value)
  return Number.isFinite(n) ? moneyFormatter.value.format(n) : String(value)
}

function qty(value: string | number | null | undefined, decimals: number | null | undefined = null): string {
  const n = Number(value ?? 0)
  if (!Number.isFinite(n)) {
    return String(value ?? '')
  }
  const d = decimals ?? 2
  return new Intl.NumberFormat(undefined, {
    minimumFractionDigits: d,
    maximumFractionDigits: d,
  }).format(n)
}

function num(value: string | number | null | undefined): string {
  const n = Number(value ?? 0)
  if (!Number.isFinite(n)) {
    return String(value ?? '')
  }
  return new Intl.NumberFormat(undefined, { maximumFractionDigits: 2 }).format(n)
}

function fmtDate(value: string | null | undefined): string {
  if (!value) {
    return ''
  }
  const match = String(value).match(/^\d{4}-\d{2}-\d{2}/)
  return match ? match[0] : String(value)
}

function printStamp(): string {
  const now = new Date()
  const pad = (n: number) => String(n).padStart(2, '0')
  return (
    `${now.getFullYear()}${pad(now.getMonth() + 1)}${pad(now.getDate())}` +
    `-${pad(now.getHours())}${pad(now.getMinutes())}${pad(now.getSeconds())}`
  )
}

onMounted(() => {
  document.title = `${props.invoice.voucher_no}-${printStamp()}`
  setTimeout(() => window.print(), 250)
})
</script>

<template>
  <div
    class="invoice mx-auto my-6 max-w-[210mm] bg-white p-10 text-[12px] text-black shadow print:my-0 print:shadow-none"
  >
    <header class="flex items-start justify-between border-b-2 border-black pb-4">
      <div class="space-y-0.5">
        <div class="text-[20px] font-bold">{{ localized(invoice.company?.name) }}</div>
        <div v-if="invoice.company?.legal_name" class="text-[11px]">{{ localized(invoice.company?.legal_name) }}</div>
        <div v-if="invoice.company?.address" class="text-[11px] whitespace-pre-line">
          {{ localized(invoice.company?.address) }}
        </div>
        <div v-if="invoice.company?.mobile || invoice.company?.email" class="text-[11px]">
          <span v-if="invoice.company?.mobile" dir="ltr" class="inline-block">{{ invoice.company?.mobile }}</span>
          <span v-if="invoice.company?.mobile && invoice.company?.email"> · </span>
          <span v-if="invoice.company?.email">{{ invoice.company?.email }}</span>
        </div>
        <div v-if="invoice.company?.tax_id" class="text-[11px]">{{ __('Tax ID') }}: {{ invoice.company?.tax_id }}</div>
      </div>
      <div class="text-end">
        <div class="text-[24px] font-bold tracking-wide">{{ __('PURCHASE INVOICE') }}</div>
        <div class="mt-2 text-[11px]">
          <div>
            <span class="font-semibold">{{ __('No.') }}:</span> {{ invoice.voucher_no }}
          </div>
          <div>
            <span class="font-semibold">{{ __('Date') }}:</span>
            <span dir="ltr" class="inline-block">{{ fmtDate(invoice.voucher_date) }}</span>
          </div>
          <div v-if="invoice.due_date">
            <span class="font-semibold">{{ __('Due date') }}:</span>
            <span dir="ltr" class="inline-block">{{ fmtDate(invoice.due_date) }}</span>
          </div>
        </div>
      </div>
    </header>

    <section class="mt-5 grid grid-cols-2 gap-6">
      <div>
        <div class="mb-1 text-[10px] font-semibold tracking-wide text-gray-600 uppercase">{{ __('Supplier') }}</div>
        <div class="font-semibold">{{ localized(invoice.supplier_account?.name) }}</div>
        <div v-if="invoice.supplier_account?.legal_name" class="text-[11px]">
          {{ localized(invoice.supplier_account?.legal_name) }}
        </div>
        <div v-if="invoice.supplier_account?.code" class="text-[11px]">
          {{ __('Code') }}: {{ invoice.supplier_account?.code }}
        </div>
        <div v-if="invoice.supplier_account?.tax_id" class="text-[11px]">
          {{ __('Tax ID') }}: {{ invoice.supplier_account?.tax_id }}
        </div>
        <div v-if="invoice.supplier_account?.vat_id" class="text-[11px]">
          {{ __('VAT ID') }}: {{ invoice.supplier_account?.vat_id }}
        </div>
        <div v-if="invoice.supplier_account?.email" class="text-[11px]">{{ invoice.supplier_account?.email }}</div>
        <div v-if="invoice.supplier_account?.mobile" class="text-[11px]">
          <span dir="ltr" class="inline-block">{{ invoice.supplier_account?.mobile }}</span>
        </div>
      </div>
      <div>
        <div class="mb-1 text-[10px] font-semibold tracking-wide text-gray-600 uppercase">{{ __('Details') }}</div>
        <table class="w-full text-[11px]">
          <tbody>
            <tr v-if="invoice.branch">
              <td class="py-0.5 pe-3 text-gray-600">{{ __('Branch') }}</td>
              <td class="py-0.5">{{ localized(invoice.branch?.name) }}</td>
            </tr>
            <tr v-if="invoice.department">
              <td class="py-0.5 pe-3 text-gray-600">{{ __('Department') }}</td>
              <td class="py-0.5">{{ localized(invoice.department?.name) }}</td>
            </tr>
            <tr v-if="invoice.purchase_order">
              <td class="py-0.5 pe-3 text-gray-600">{{ __('Purchase Order') }}</td>
              <td class="py-0.5">{{ invoice.purchase_order?.voucher_no }}</td>
            </tr>
            <tr v-if="invoice.goods_receipt">
              <td class="py-0.5 pe-3 text-gray-600">{{ __('Goods Receipt') }}</td>
              <td class="py-0.5">{{ invoice.goods_receipt?.voucher_no }}</td>
            </tr>
            <tr v-if="invoice.buyer">
              <td class="py-0.5 pe-3 text-gray-600">{{ __('Buyer') }}</td>
              <td class="py-0.5">{{ invoice.buyer?.name }}</td>
            </tr>
            <tr>
              <td class="py-0.5 pe-3 text-gray-600">{{ __('Currency') }}</td>
              <td class="py-0.5">
                {{ invoice.currency?.code }}<span v-if="Number(invoice.fx_rate) !== 1"> @ {{ invoice.fx_rate }}</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <section class="mt-6">
      <table class="w-full border-collapse text-[11px]">
        <thead>
          <tr class="border-y-2 border-black">
            <th class="py-2 ps-1 pe-2 text-start">#</th>
            <th class="py-2 pe-2 text-start">{{ __('Item') }}</th>
            <th class="py-2 pe-2 text-start">{{ __('Warehouse') }}</th>
            <th class="py-2 pe-2 text-end">{{ __('Qty') }}</th>
            <th class="py-2 pe-2 text-start">{{ __('UOM') }}</th>
            <th class="py-2 pe-2 text-end">{{ __('Rate') }}</th>
            <th class="py-2 pe-2 text-end">{{ __('Disc %') }}</th>
            <th class="py-2 pe-2 text-start">{{ __('Tax') }}</th>
            <th class="py-2 pe-1 text-end">{{ __('Amount') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(line, idx) in invoice.lines" :key="line.id" class="border-b border-gray-300 align-top">
            <td class="py-1.5 ps-1 pe-2">{{ idx + 1 }}</td>
            <td class="py-1.5 pe-2">
              <div class="font-semibold">{{ localized(line.item?.name) }}</div>
              <div v-if="line.item?.code" class="text-[10px] text-gray-600">{{ line.item?.code }}</div>
            </td>
            <td class="py-1.5 pe-2">{{ localized(line.warehouse?.name) }}</td>
            <td class="py-1.5 pe-2 text-end tabular-nums">{{ qty(line.qty, line.uom?.decimals) }}</td>
            <td class="py-1.5 pe-2">{{ localized(line.uom?.name) }}</td>
            <td class="py-1.5 pe-2 text-end tabular-nums">{{ fmt(line.rate) }}</td>
            <td class="py-1.5 pe-2 text-end tabular-nums">{{ num(line.discount_pct) }}</td>
            <td class="py-1.5 pe-2">{{ line.tax_code?.code || '—' }}</td>
            <td class="py-1.5 pe-1 text-end tabular-nums">{{ fmt(line.amount) }}</td>
          </tr>
          <tr v-if="!invoice.lines?.length">
            <td colspan="9" class="py-4 text-center text-gray-500">{{ __('No items.') }}</td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="mt-6 flex justify-end">
      <table class="w-[60%] max-w-[320px] text-[12px]">
        <tbody>
          <tr>
            <td class="py-1 pe-3 text-gray-600">{{ __('Subtotal') }}</td>
            <td class="py-1 text-end tabular-nums">{{ fmt(invoice.total_amount) }}</td>
          </tr>
          <tr v-if="Number(invoice.total_discount) > 0">
            <td class="py-1 pe-3 text-gray-600">{{ __('Discount') }}</td>
            <td class="py-1 text-end tabular-nums">-{{ fmt(invoice.total_discount) }}</td>
          </tr>
          <tr v-if="Number(invoice.total_tax) > 0">
            <td class="py-1 pe-3 text-gray-600">{{ __('Tax') }}</td>
            <td class="py-1 text-end tabular-nums">{{ fmt(invoice.total_tax) }}</td>
          </tr>
          <tr class="border-t-2 border-black text-[14px] font-bold">
            <td class="py-2 pe-3">{{ __('Grand Total') }}</td>
            <td class="py-2 text-end tabular-nums">{{ invoice.currency?.code }} {{ fmt(invoice.grand_total) }}</td>
          </tr>
          <tr v-if="Number(invoice.paid_amount) > 0">
            <td class="py-1 pe-3 text-gray-600">{{ __('Paid') }}</td>
            <td class="py-1 text-end tabular-nums">{{ fmt(invoice.paid_amount) }}</td>
          </tr>
          <tr v-if="Number(invoice.outstanding) > 0" class="font-semibold">
            <td class="py-1 pe-3">{{ __('Outstanding') }}</td>
            <td class="py-1 text-end tabular-nums">{{ fmt(invoice.outstanding) }}</td>
          </tr>
        </tbody>
      </table>
    </section>

    <section v-if="invoice.notes && localized(invoice.notes)" class="mt-6 border-t border-gray-300 pt-3">
      <div class="mb-1 text-[10px] font-semibold tracking-wide text-gray-600 uppercase">{{ __('Notes') }}</div>
      <div class="text-[11px] whitespace-pre-line">{{ localized(invoice.notes) }}</div>
    </section>

    <footer class="mt-12 grid grid-cols-2 gap-10 text-[11px]">
      <div>
        <div class="border-t border-black pt-1 text-center text-gray-700">{{ __('Prepared by') }}</div>
      </div>
      <div>
        <div class="border-t border-black pt-1 text-center text-gray-700">{{ __('Authorised signature') }}</div>
      </div>
    </footer>
  </div>
</template>

<style>
@media print {
  @page {
    margin: 12mm;
    size: A4;
  }
  body {
    background: white;
  }
  .invoice {
    box-shadow: none !important;
    padding: 0 !important;
    margin: 0 !important;
  }
}
</style>
