<script setup lang="ts">
import { ref } from 'vue'
import { router, useHttp } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import useTranslation from '@/composables/useTranslation'
import { toast } from 'vue-sonner'
import { Calculator } from 'lucide-vue-next'

type LocalizedName = Record<string, string> | string

const props = defineProps<{
  bootstrap: {
    cashboxes: Array<{ id: number; code: string; name: LocalizedName; currency_id: number }>
    warehouses: Array<{ id: number; code: string; name: LocalizedName }>
    currencies: Array<{ id: number; code: string; symbol: string | null }>
    walk_in_customer: { id: number; code: string; name: LocalizedName } | null
    default_currency_id: number | null
  }
  openUrl: string
}>()

// resolveTranslatable handles JSON bags ({ en: '…', ar: '…' }) coming from
// HasTranslations attributes — picks the active locale, falls back to the
// app default, then to the first non-empty value.
const { __, resolveTranslatable: localized } = useTranslation()
const success = (msg: string) => toast.success(msg)
const error = (msg: string) => toast.error(msg)

const cashbox_id = ref<string>('')
const warehouse_id = ref<string>('')
const currency_id = ref<string>(props.bootstrap.default_currency_id ? String(props.bootstrap.default_currency_id) : '')
const opening_cash = ref('0.00')
const customer_account_id = ref<string>(props.bootstrap.walk_in_customer ? String(props.bootstrap.walk_in_customer.id) : '')

// eslint-disable-next-line @typescript-eslint/no-explicit-any
const http = useHttp({
  cashbox_id: null,
  warehouse_id: null,
  currency_id: null,
  customer_account_id: null,
  opening_cash: '0',
} as any) as unknown as Record<string, unknown> & {
  processing: boolean
  post(url: string, opts?: object): Promise<unknown>
}

function submit() {
  if (!cashbox_id.value || !currency_id.value) {
    error(__('Cashbox and currency are required.'))
    return
  }

  http.cashbox_id = Number(cashbox_id.value)
  http.warehouse_id = warehouse_id.value ? Number(warehouse_id.value) : null
  http.currency_id = Number(currency_id.value)
  http.customer_account_id = customer_account_id.value ? Number(customer_account_id.value) : null
  http.opening_cash = opening_cash.value || '0'

  http.post(props.openUrl, {
    onSuccess: (response: unknown) => {
      const r = response as { message?: string; redirect?: string }
      success(r?.message ?? __('Session opened.'))
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
  <div class="flex flex-1 items-center justify-center overflow-y-auto bg-paper-100 p-4">
    <div class="w-full max-w-md rounded-sm border border-ink-200 bg-white p-5 shadow-sm sm:p-6">
      <div class="mb-5 flex items-center gap-3">
        <Calculator class="size-7 text-brand-600" />
        <div>
          <div class="text-[16px] font-semibold">{{ __('Open POS Session') }}</div>
          <div class="text-[13px] text-ink-500">{{ __('Pick your drawer and starting cash to begin selling.') }}</div>
        </div>
      </div>

      <div class="space-y-4">
        <label class="block">
          <span class="mb-1.5 block text-[13px] font-medium text-ink-700">{{ __('Cashbox') }}</span>
          <Select v-model="cashbox_id">
            <SelectTrigger class="h-12 text-[15px]">
              <SelectValue :placeholder="__('Pick cashbox')" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="c in bootstrap.cashboxes" :key="c.id" :value="String(c.id)" class="text-[15px]">{{ localized(c.name) }}</SelectItem>
            </SelectContent>
          </Select>
        </label>

        <label class="block">
          <span class="mb-1.5 block text-[13px] font-medium text-ink-700">{{ __('Warehouse') }}</span>
          <Select v-model="warehouse_id">
            <SelectTrigger class="h-12 text-[15px]">
              <SelectValue :placeholder="__('Default per item')" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="w in bootstrap.warehouses" :key="w.id" :value="String(w.id)" class="text-[15px]">{{ localized(w.name) }}</SelectItem>
            </SelectContent>
          </Select>
        </label>

        <label class="block">
          <span class="mb-1.5 block text-[13px] font-medium text-ink-700">{{ __('Currency') }}</span>
          <Select v-model="currency_id">
            <SelectTrigger class="h-12 text-[15px]">
              <SelectValue :placeholder="__('Pick currency')" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="c in bootstrap.currencies" :key="c.id" :value="String(c.id)" class="text-[15px]">{{ c.code }}</SelectItem>
            </SelectContent>
          </Select>
        </label>

        <label class="block">
          <span class="mb-1.5 block text-[13px] font-medium text-ink-700">{{ __('Opening cash') }}</span>
          <Input
            v-model="opening_cash"
            type="number"
            inputmode="decimal"
            step="0.01"
            min="0"
            class="h-12 text-end text-[16px] tabular-nums"
          />
        </label>
      </div>

      <Button class="mt-6 h-12 w-full text-[15px]" :disabled="http.processing" @click="submit">
        {{ http.processing ? __('Opening…') : __('Open session') }}
      </Button>
    </div>
  </div>
</template>
