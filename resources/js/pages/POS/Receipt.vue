<script setup lang="ts">
import { onMounted } from 'vue'
import useTranslation from '@/composables/useTranslation'

interface InvoiceLine {
  id: number
  qty: string
  rate: string
  amount: string
  discount_amount: string
  tax_amount: string
  item: { code: string; name: Record<string, string> | string } | null
  uom: { name: Record<string, string> | string } | null
}

interface Invoice {
  voucher_no: string
  voucher_date: string
  grand_total: string
  total_amount: string
  total_tax: string
  total_discount: string
  paid_amount: string
  customer_account: { code: string; name: Record<string, string> | string } | null
  currency: { code: string; symbol: string | null } | null
  company: { code: string; name: Record<string, string> | string } | null
  branch: { code: string; name: Record<string, string> | string } | null
  salesperson: { name: string } | null
  pos_session: { voucher_no: string; cashier: { name: string } | null } | null
  lines: InvoiceLine[]
}

const props = defineProps<{ invoice: Invoice }>()
const { __ } = useTranslation()

const localized = (n: Record<string, string> | string | null | undefined) => {
  if (!n) return ''
  return typeof n === 'object' ? Object.values(n)[0] : n
}

onMounted(() => {
  // Auto-trigger the browser print dialog so cashiers can print straight to a
  // thermal printer without an extra click.
  setTimeout(() => window.print(), 200)
})
</script>

<template>
  <div class="receipt mx-auto max-w-[80mm] p-3 font-mono text-[11px] text-black">
    <div class="text-center">
      <div class="text-[14px] font-bold">{{ localized(invoice.company?.name) }}</div>
      <div v-if="invoice.branch">{{ localized(invoice.branch.name) }}</div>
      <div class="my-2 border-b border-dashed border-black"></div>
    </div>

    <div class="space-y-0.5">
      <div class="flex justify-between"><span>{{ __('Receipt') }} #</span><span class="font-semibold">{{ invoice.voucher_no }}</span></div>
      <div class="flex justify-between"><span>{{ __('Date') }}</span><span>{{ invoice.voucher_date }}</span></div>
      <div v-if="invoice.pos_session" class="flex justify-between"><span>{{ __('Session') }}</span><span>{{ invoice.pos_session.voucher_no }}</span></div>
      <div v-if="invoice.pos_session?.cashier" class="flex justify-between"><span>{{ __('Cashier') }}</span><span>{{ invoice.pos_session.cashier.name }}</span></div>
      <div v-if="invoice.customer_account" class="flex justify-between"><span>{{ __('Customer') }}</span><span>{{ localized(invoice.customer_account.name) }}</span></div>
    </div>

    <div class="my-2 border-b border-dashed border-black"></div>

    <table class="w-full">
      <thead>
        <tr class="text-[10px]">
          <th class="text-start">{{ __('Item') }}</th>
          <th class="text-end">{{ __('Qty') }}</th>
          <th class="text-end">{{ __('Price') }}</th>
          <th class="text-end">{{ __('Amt') }}</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="line in invoice.lines" :key="line.id" class="align-top">
          <td class="pe-1">
            <div class="font-semibold">{{ localized(line.item?.name) }}</div>
            <div class="text-[10px]">{{ line.item?.code }}</div>
          </td>
          <td class="text-end">{{ Number(line.qty).toFixed(2) }}</td>
          <td class="text-end">{{ Number(line.rate).toFixed(2) }}</td>
          <td class="text-end">{{ Number(line.amount).toFixed(2) }}</td>
        </tr>
      </tbody>
    </table>

    <div class="my-2 border-b border-dashed border-black"></div>

    <div class="space-y-0.5">
      <div class="flex justify-between"><span>{{ __('Subtotal') }}</span><span>{{ Number(invoice.total_amount).toFixed(2) }}</span></div>
      <div v-if="Number(invoice.total_discount) > 0" class="flex justify-between"><span>{{ __('Discount') }}</span><span>-{{ Number(invoice.total_discount).toFixed(2) }}</span></div>
      <div v-if="Number(invoice.total_tax) > 0" class="flex justify-between"><span>{{ __('Tax') }}</span><span>{{ Number(invoice.total_tax).toFixed(2) }}</span></div>
      <div class="mt-1 flex justify-between border-t border-black pt-1 text-[13px] font-bold">
        <span>{{ __('TOTAL') }}</span>
        <span>{{ invoice.currency?.code }} {{ Number(invoice.grand_total).toFixed(2) }}</span>
      </div>
      <div class="flex justify-between"><span>{{ __('Paid') }}</span><span>{{ Number(invoice.paid_amount).toFixed(2) }}</span></div>
    </div>

    <div class="my-3 border-b border-dashed border-black"></div>
    <div class="text-center text-[11px]">{{ __('Thank you!') }}</div>
  </div>
</template>

<style>
@media print {
  @page { margin: 0; size: 80mm auto; }
  body { background: white; }
  .receipt { max-width: 80mm; margin: 0; }
}
</style>
