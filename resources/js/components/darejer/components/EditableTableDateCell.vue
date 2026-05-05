<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import { Calendar } from '@/components/ui/calendar'
import { CalendarIcon, X } from 'lucide-vue-next'
import useTranslation from '@/composables/useTranslation'
import { CalendarDate, DateFormatter, getLocalTimeZone, parseDate } from '@internationalized/date'

const { __ } = useTranslation()

const props = defineProps<{
  modelValue: unknown
  disabled?: boolean
  placeholder?: string
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: string | null): void
}>()

const open = ref(false)

// Accept either a plain `YYYY-MM-DD` or a Laravel/Carbon ISO string with a
// time component (e.g. `2026-04-25T00:00:00.000000Z`). `parseDate()` only
// handles the former, so trim to the date portion first.
function parseInitial(v: unknown): CalendarDate | undefined {
  if (v == null || v === '') return undefined
  const s = String(v).slice(0, 10)
  if (!/^\d{4}-\d{2}-\d{2}$/.test(s)) return undefined
  try {
    return parseDate(s)
  } catch {
    return undefined
  }
}

const selected = ref<CalendarDate | undefined>(parseInitial(props.modelValue))

watch(
  () => props.modelValue,
  (next) => {
    const parsed = parseInitial(next)
    if (parsed && (!selected.value || selected.value.compare(parsed) !== 0)) {
      selected.value = parsed
    } else if ((next == null || next === '') && selected.value) {
      selected.value = undefined
    }
  },
)

const df = new DateFormatter('en-US', { dateStyle: 'medium' })

const displayValue = computed(() =>
  selected.value ? df.format(selected.value.toDate(getLocalTimeZone())) : null,
)

const placeholderText = computed(() => props.placeholder ?? __('Pick a date…'))

function onSelect(date: unknown) {
  const d = Array.isArray(date) ? date[0] : (date as CalendarDate | undefined)
  selected.value = d
  if (d) open.value = false
  emit('update:modelValue', d ? d.toString() : null)
}

function clear() {
  selected.value = undefined
  emit('update:modelValue', null)
}
</script>

<template>
  <Popover :open="open" @update:open="open = $event">
    <PopoverTrigger as-child>
      <button
        type="button"
        :disabled="disabled"
        class="flex h-full w-full items-center justify-between border-none bg-transparent px-2.5 text-start text-sm transition-colors duration-100 outline-none focus:bg-brand-50 disabled:cursor-not-allowed disabled:opacity-50"
      >
        <span class="truncate" :class="selected ? 'text-ink-900' : `text-ink-400`">
          {{ displayValue ?? placeholderText }}
        </span>
        <div class="ms-1 flex shrink-0 items-center gap-1">
          <button
            v-if="selected && !disabled"
            type="button"
            class="text-ink-300 transition-colors hover:text-ink-500"
            @click.stop="clear"
          >
            <X class="h-3 w-3" />
          </button>
          <CalendarIcon class="h-3.5 w-3.5 text-ink-300" />
        </div>
      </button>
    </PopoverTrigger>

    <PopoverContent class="w-auto p-0" align="start">
      <Calendar
        :model-value="selected as any"
        initial-focus
        class="border-none"
        @update:model-value="onSelect"
      />
    </PopoverContent>
  </Popover>
</template>
