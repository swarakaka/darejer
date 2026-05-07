<script setup lang="ts">
import { ref, onMounted, watch, nextTick } from 'vue'
import { useHttp } from '@inertiajs/vue3'
import { Input } from '@/components/ui/input'
import useTranslation from '@/composables/useTranslation'
import { Search, Barcode } from 'lucide-vue-next'

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

const props = defineProps<{ searchUrl: string }>()
const emit = defineEmits<{ select: [item: PosItem] }>()

const { __ } = useTranslation()

const term = ref('')
const items = ref<PosItem[]>([])
const searchInput = ref<HTMLInputElement | null>(null)
// useHttp serializes its bag as the request body / query string —
// `q` is set on the bag before each .get() call.
// eslint-disable-next-line @typescript-eslint/no-explicit-any
const http = useHttp({ q: '' } as any) as unknown as Record<string, unknown> & {
  get(url: string, opts?: object): Promise<unknown>
}

const localized = (name: Record<string, string> | string) =>
  typeof name === 'object' ? Object.values(name)[0] : name

let lastTerm = ''

async function load() {
  const q = term.value.trim()
  lastTerm = q
  http.q = q
  await http.get(props.searchUrl, {
    onSuccess: (response: unknown) => {
      // Stale guard: discard responses that don't match the latest typed term.
      if (q !== lastTerm) return
      const r = response as { data?: PosItem[] }
      items.value = r?.data ?? []
    },
  })
}

let timer: ReturnType<typeof setTimeout> | null = null
watch(term, () => {
  if (timer) clearTimeout(timer)
  timer = setTimeout(load, 180)
})

function onScannerEnter() {
  // A barcode reader fires keystrokes ending in Enter. If the response comes
  // back with exactly one match, auto-add it and clear the input so the next
  // scan starts fresh.
  load().then(() => {
    if (items.value.length === 1) {
      emit('select', items.value[0])
      term.value = ''
      nextTick(() => searchInput.value?.focus())
    }
  })
}

onMounted(() => {
  load()
  searchInput.value?.focus()
})
</script>

<template>
  <div class="flex h-full min-h-0 flex-col rounded-sm border border-ink-200 bg-white">
    <div class="border-b border-ink-200 p-3">
      <div class="relative">
        <Barcode class="pointer-events-none absolute start-2 top-1/2 size-4 -translate-y-1/2 text-ink-500" />
        <Input
          ref="searchInput"
          v-model="term"
          :placeholder="__('Scan barcode or search by code / name…')"
          class="h-10 ps-8 text-[14px]"
          @keydown.enter.prevent="onScannerEnter"
        />
      </div>
    </div>

    <div class="grid auto-rows-min grid-cols-2 gap-2 overflow-y-auto p-3 sm:grid-cols-3 xl:grid-cols-4">
      <button
        v-for="item in items"
        :key="item.id"
        type="button"
        class="flex flex-col items-start rounded-sm border border-ink-200 bg-white p-3 text-start transition hover:border-brand-400 hover:bg-brand-50 active:bg-brand-100"
        @click="emit('select', item)"
      >
        <div class="line-clamp-2 text-[13px] font-semibold text-ink-900">{{ localized(item.name) }}</div>
        <div class="mt-1 text-[11px] text-ink-500">{{ item.code }}</div>
        <div class="mt-2 text-[14px] font-semibold text-brand-700">{{ Number(item.selling_price).toFixed(2) }}</div>
      </button>
      <div
        v-if="!items.length"
        class="col-span-full p-8 text-center text-[13px] text-ink-500"
      >
        <Search class="mx-auto mb-2 size-6 opacity-50" />
        {{ term ? __('No matching items.') : __('No POS items configured.') }}
      </div>
    </div>
  </div>
</template>
