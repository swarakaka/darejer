<script setup lang="ts">
import { ref, watch } from 'vue'
import { useHttp } from '@inertiajs/vue3'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import useTranslation from '@/composables/useTranslation'

interface Customer {
  id: number
  code: string
  name: Record<string, string> | string
}

const props = defineProps<{ open: boolean; searchUrl: string }>()
const emit = defineEmits<{ 'update:open': [v: boolean]; select: [c: Customer] }>()

const { __, resolveTranslatable: localized } = useTranslation()

const term = ref('')
const customers = ref<Customer[]>([])
// eslint-disable-next-line @typescript-eslint/no-explicit-any
const http = useHttp({ q: '' } as any) as unknown as Record<string, unknown> & {
  get(url: string, opts?: object): Promise<unknown>
}


let lastTerm = ''
async function load() {
  const q = term.value.trim()
  lastTerm = q
  http.q = q
  await http.get(props.searchUrl, {
    onSuccess: (response: unknown) => {
      if (q !== lastTerm) return
      const r = response as { data?: Customer[] }
      customers.value = r?.data ?? []
    },
  })
}

watch(
  () => props.open,
  (o) => {
    if (o) load()
  },
)

let timer: ReturnType<typeof setTimeout> | null = null
watch(term, () => {
  if (timer) clearTimeout(timer)
  timer = setTimeout(load, 180)
})
</script>

<template>
  <Dialog :open="open" @update:open="(v) => emit('update:open', v)">
    <DialogContent class="max-w-md">
      <DialogHeader>
        <DialogTitle>{{ __('Select customer') }}</DialogTitle>
      </DialogHeader>

      <Input
        v-model="term"
        :placeholder="__('Search by code or name…')"
        class="h-12 text-[15px]"
        autofocus
      />

      <div class="max-h-[60vh] overflow-y-auto">
        <button
          v-for="c in customers"
          :key="c.id"
          type="button"
          class="block w-full rounded-sm px-3 py-3 text-start hover:bg-paper-100 active:bg-paper-150"
          @click="emit('select', c)"
        >
          <div class="text-[15px] font-medium">{{ localized(c.name) }}</div>
          <div class="text-[12px] text-ink-500">{{ c.code }}</div>
        </button>
        <div v-if="!customers.length" class="p-8 text-center text-[14px] text-ink-500">
          {{ __('No matching customers.') }}
        </div>
      </div>
    </DialogContent>
  </Dialog>
</template>
