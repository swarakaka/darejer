<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Checkbox } from '@/components/ui/checkbox'
import { Input } from '@/components/ui/input'
import { Search, Loader2 } from 'lucide-vue-next'
import { useDataUrl } from '@/composables/useDataUrl'
import FieldWrapper from '@/components/darejer/FieldWrapper.vue'
import useTranslation from '@/composables/useTranslation'
import type { DarejerComponent } from '@/types/darejer'

const { __ } = useTranslation()

const props = defineProps<{
  component: DarejerComponent
  record: Record<string, unknown>
  errors: Record<string, string>
  formData?: Record<string, unknown>
}>()

const emit = defineEmits<{ (e: 'update', name: string, value: unknown): void }>()

type Option = { value: string; label: string; subtitle?: string }
type Group = { key: string; label: string; options: Option[] }

const options = ref<Option[]>([])
const loading = ref(false)
const search = ref('')

const rawValue =
  (props.formData ?? props.record)[props.component.name] ?? props.component.default ?? []

const selected = ref<string[]>(
  Array.isArray(rawValue) ? rawValue.map(String) : rawValue != null ? [String(rawValue)] : [],
)

const selectedSet = computed(() => new Set(selected.value))

const staticOptions = computed(
  () => (props.component.staticOptions as Option[] | undefined) ?? null,
)

const subtitleField = computed(() => (props.component.subtitleField as string | null) ?? null)

const { load } = useDataUrl<Record<string, unknown>>(
  props.component.dataUrl as string | undefined,
  {
    perPage: 500,
    combobox: true,
    keyField: (props.component.keyField as string) ?? 'id',
    labelField: (props.component.labelField as string) ?? 'name',
    subtitleField: subtitleField.value ?? undefined,
  },
)

function normalize(items: Record<string, unknown>[]): Option[] {
  const keyField = (props.component.keyField as string) ?? 'id'
  const labelField = (props.component.labelField as string) ?? 'name'
  const subField = subtitleField.value
  return items.map((row) => {
    const label = String(row.label ?? row[labelField] ?? '')
    const subtitleRaw = subField
      ? String(row.subtitle ?? row[subField] ?? '')
      : ''
    return {
      value: String(row.value ?? row[keyField] ?? ''),
      label: label || subtitleRaw,
      subtitle: subtitleRaw && subtitleRaw !== label ? subtitleRaw : undefined,
    }
  })
}

async function fetchOptions(): Promise<void> {
  if (staticOptions.value) {
    options.value = staticOptions.value
    return
  }
  if (!props.component.dataUrl) {
    options.value = []
    return
  }
  loading.value = true
  const result = await load({ page: 1 })
  if (result) {
    options.value = normalize(result.data as Record<string, unknown>[])
  }
  loading.value = false
}

onMounted(fetchOptions)

const filteredOptions = computed<Option[]>(() => {
  const term = search.value.trim().toLowerCase()
  if (!term) return options.value
  return options.value.filter(
    (o) =>
      o.label.toLowerCase().includes(term) ||
      (o.subtitle ? o.subtitle.toLowerCase().includes(term) : false),
  )
})

const separator = computed(() => (props.component.groupBySeparator as string | null) ?? null)

function groupKey(option: Option, sep: string): string {
  // When a subtitle is present (e.g. the dotted technical name), prefer it
  // for grouping so the user-facing label can be a free-form description.
  const source = option.subtitle && option.subtitle.includes(sep) ? option.subtitle : option.label
  const idx = source.indexOf(sep)
  return idx === -1 ? source : source.slice(0, idx)
}

const groups = computed<Group[]>(() => {
  const sep = separator.value
  if (!sep) {
    return [{ key: '__all', label: '', options: filteredOptions.value }]
  }
  const map = new Map<string, Option[]>()
  for (const option of filteredOptions.value) {
    const key = groupKey(option, sep)
    if (!map.has(key)) map.set(key, [])
    map.get(key)!.push(option)
  }
  return Array.from(map.entries())
    .sort(([a], [b]) => a.localeCompare(b))
    .map(([key, opts]) => ({ key, label: key, options: opts }))
})

const columns = computed(() => {
  const c = Number(props.component.columns ?? 1)
  return Number.isFinite(c) && c > 0 ? c : 1
})

const gridClass = computed(() => {
  const map: Record<number, string> = {
    1: 'grid-cols-1',
    2: 'grid-cols-1 sm:grid-cols-2',
    3: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
    4: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4',
  }
  return map[columns.value] ?? 'grid-cols-1'
})

function isChecked(value: string): boolean {
  return selectedSet.value.has(value)
}

function toggle(value: string, next: boolean | 'indeterminate'): void {
  const checked = next === 'indeterminate' ? false : next
  const current = new Set(selected.value)
  if (checked) {
    current.add(value)
  } else {
    current.delete(value)
  }
  selected.value = Array.from(current)
  emit('update', props.component.name, selected.value)
}

function groupState(group: Group): 'all' | 'some' | 'none' {
  let hit = 0
  for (const option of group.options) {
    if (selectedSet.value.has(option.value)) hit++
  }
  if (hit === 0) return 'none'
  if (hit === group.options.length) return 'all'
  return 'some'
}

function toggleGroup(group: Group, next: boolean | 'indeterminate'): void {
  const target = next === 'indeterminate' ? false : next
  const current = new Set(selected.value)
  for (const option of group.options) {
    if (target) {
      current.add(option.value)
    } else {
      current.delete(option.value)
    }
  }
  selected.value = Array.from(current)
  emit('update', props.component.name, selected.value)
}

const totalSelected = computed(() => selected.value.length)
const totalVisible = computed(() => filteredOptions.value.length)
const disabled = computed(() => !!props.component.disabled)
</script>

<template>
  <FieldWrapper :component="component" :record="record" :errors="errors" :form-data="formData">
    <template #default="{ hasError }">
      <div
        class="flex flex-col gap-3 rounded-md border bg-card p-3"
        :class="hasError ? 'border-danger-600' : 'border-ink-200'"
      >
        <div class="flex flex-wrap items-center justify-between gap-2">
          <div v-if="component.searchable" class="relative flex-1 min-w-[180px]">
            <Search class="absolute left-2 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-ink-400" />
            <Input
              v-model="search"
              :placeholder="__('Search...')"
              class="h-8 pl-7 text-xs"
              :disabled="disabled"
            />
          </div>
          <p class="text-xs text-ink-500">
            {{ totalSelected }} / {{ totalVisible }} {{ __('selected') }}
          </p>
        </div>

        <div v-if="loading" class="flex items-center gap-2 text-xs text-ink-500">
          <Loader2 class="h-3.5 w-3.5 animate-spin" />
          {{ __('Loading...') }}
        </div>

        <p v-else-if="filteredOptions.length === 0" class="text-xs text-ink-400">
          {{ __('No options available.') }}
        </p>

        <div v-else class="flex flex-col gap-4">
          <div v-for="group in groups" :key="group.key" class="flex flex-col gap-2">
            <div
              v-if="group.label"
              class="flex items-center gap-2 border-b border-ink-100 pb-1.5"
            >
              <Checkbox
                :id="`${component.name}-grp-${group.key}`"
                :model-value="
                  groupState(group) === 'all'
                    ? true
                    : groupState(group) === 'some'
                      ? 'indeterminate'
                      : false
                "
                :disabled="disabled"
                @update:model-value="(v) => toggleGroup(group, v)"
              />
              <label
                :for="`${component.name}-grp-${group.key}`"
                class="cursor-pointer text-xs font-semibold tracking-tight text-ink-700 select-none"
              >
                {{ group.label }}
              </label>
              <span class="text-xs text-ink-400">({{ group.options.length }})</span>
            </div>

            <div class="grid gap-x-4 gap-y-2" :class="gridClass">
              <div
                v-for="option in group.options"
                :key="option.value"
                class="flex items-start gap-2"
              >
                <Checkbox
                  :id="`${component.name}-${option.value}`"
                  :model-value="isChecked(option.value)"
                  :disabled="disabled"
                  class="mt-0.5"
                  @update:model-value="(v) => toggle(option.value, v)"
                />
                <label
                  :for="`${component.name}-${option.value}`"
                  class="flex cursor-pointer flex-col leading-tight select-none"
                >
                  <span class="text-xs text-ink-700">{{ option.label }}</span>
                  <span
                    v-if="option.subtitle"
                    class="font-mono text-[10px] text-ink-400"
                  >
                    {{ option.subtitle }}
                  </span>
                </label>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>
  </FieldWrapper>
</template>
