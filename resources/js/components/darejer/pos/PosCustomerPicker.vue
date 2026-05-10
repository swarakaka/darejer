<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useHttp } from '@inertiajs/vue3'
import { handleHttpException } from '@/lib/handleHttpException'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Phone, RotateCcw, UserPlus } from 'lucide-vue-next'
import useTranslation from '@/composables/useTranslation'

interface Customer {
  id: number
  code: string
  name: Record<string, string> | string
  phone?: string | null
}

const props = defineProps<{
  open: boolean
  searchUrl: string
  walkInCustomer?: Customer | null
  quickAddUrl?: string | null
}>()
const emit = defineEmits<{
  'update:open': [v: boolean]
  select: [c: Customer]
}>()

const { __, resolveTranslatable: localized } = useTranslation()

const term = ref('')
const customers = ref<Customer[]>([])
// eslint-disable-next-line @typescript-eslint/no-explicit-any
const http = useHttp({ q: '' } as any) as unknown as Record<string, unknown> & {
  get(url: string, opts?: object): Promise<unknown>
}
// eslint-disable-next-line @typescript-eslint/no-explicit-any
const quickHttp = useHttp({ phone: '', name: '' } as any) as unknown as Record<string, unknown> & {
  processing: boolean
  post(url: string, opts?: object): Promise<unknown>
}

// A "phone-ish" string is what the picker treats as a candidate for quick-add
// when no match is found. Six chars is roughly the shortest mobile prefix that
// is still useful as a contact handle.
const phoneRegex = /^[\d\s+\-()]{6,}$/

const isPhoneTerm = computed(() => phoneRegex.test(term.value.trim()))
const trimmedTerm = computed(() => term.value.trim())
const noResults = computed(() => trimmedTerm.value !== '' && customers.value.length === 0)
const canQuickAdd = computed(() => Boolean(props.quickAddUrl) && noResults.value && isPhoneTerm.value)

let lastTerm = ''
async function load() {
  const q = trimmedTerm.value
  lastTerm = q
  http.q = q
  await http.get(props.searchUrl, {
    onSuccess: (response: unknown) => {
      if (q !== lastTerm) return
      const r = response as { data?: Customer[] } | Customer[]
      customers.value = Array.isArray(r) ? r : (r?.data ?? [])
    },
    onHttpException: (response: { status: number }) => {
      handleHttpException(response)
    },
  })
}

watch(
  () => props.open,
  (o) => {
    if (o) {
      term.value = ''
      load()
    }
  },
)

let timer: ReturnType<typeof setTimeout> | null = null
watch(term, () => {
  if (timer) clearTimeout(timer)
  timer = setTimeout(load, 180)
})

function pickWalkIn() {
  if (props.walkInCustomer) {
    emit('select', props.walkInCustomer)
    emit('update:open', false)
  }
}

function quickAdd() {
  if (!props.quickAddUrl || !canQuickAdd.value) return
  quickHttp.phone = trimmedTerm.value
  quickHttp.name = ''
  quickHttp.post(props.quickAddUrl, {
    onSuccess: (response: unknown) => {
      const r = response as { data?: Customer } | Customer
      const created = (r as { data?: Customer })?.data ?? (r as Customer)
      if (created?.id) {
        emit('select', created)
        emit('update:open', false)
      }
    },
    onHttpException: (response: { status: number }) => {
      handleHttpException(response)
    },
  })
}
</script>

<template>
  <Dialog :open="open" @update:open="(v) => emit('update:open', v)">
    <DialogContent class="max-w-md">
      <DialogHeader>
        <DialogTitle>{{ __('Select customer') }}</DialogTitle>
      </DialogHeader>

      <div class="relative">
        <Phone class="absolute start-3 top-1/2 size-4 -translate-y-1/2 text-ink-400" />
        <Input
          v-model="term"
          :placeholder="__('Phone, code or name…')"
          class="h-12 ps-9 text-[15px]"
          autofocus
          @keydown.escape="pickWalkIn"
        />
      </div>

      <div class="max-h-[50vh] overflow-y-auto">
        <button
          v-for="c in customers"
          :key="c.id"
          type="button"
          class="block w-full rounded-sm px-3 py-3 text-start hover:bg-paper-100 active:bg-paper-150"
          @click="emit('select', c); emit('update:open', false)"
        >
          <div class="text-[15px] font-medium">{{ localized(c.name) }}</div>
          <div class="flex items-center gap-2 text-[12px] text-ink-500">
            <span>{{ c.code }}</span>
            <span v-if="c.phone">· {{ c.phone }}</span>
          </div>
        </button>
        <div v-if="noResults && !canQuickAdd" class="p-8 text-center text-[14px] text-ink-500">
          {{ __('No matching customers.') }}
        </div>
      </div>

      <div v-if="canQuickAdd" class="border-t border-ink-200 pt-3">
        <Button
          variant="outline"
          class="h-11 w-full text-[14px]"
          :disabled="quickHttp.processing"
          @click="quickAdd"
        >
          <UserPlus class="size-4" />
          {{ __('Quick add :phone', { phone: trimmedTerm }) }}
        </Button>
      </div>

      <div v-if="walkInCustomer" class="border-t border-ink-200 pt-3">
        <Button
          variant="ghost"
          class="h-11 w-full text-[14px] text-ink-700"
          @click="pickWalkIn"
        >
          <RotateCcw class="size-4" />
          {{ __('Use :name', { name: localized(walkInCustomer.name) }) }}
        </Button>
      </div>
    </DialogContent>
  </Dialog>
</template>
