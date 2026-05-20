<script setup lang="ts">
import { useHttp } from '@inertiajs/vue3'
import { Check, X, Pencil } from 'lucide-vue-next'
import { ref, computed } from 'vue'
import FieldWrapper from '@/components/darejer/FieldWrapper.vue'
import { handleHttpException } from '@/lib/handleHttpException'
import type { DarejerComponent } from '@/types/darejer'

const props = defineProps<{
  component: DarejerComponent
  record: Record<string, unknown>
  errors: Record<string, string>
  formData?: Record<string, unknown>
}>()

const emit = defineEmits<{ (e: 'update', name: string, value: unknown): void }>()

const displayField = computed(() => (props.component.displayField as string) ?? 'name')
const editField = computed(() => (props.component.editField as string) ?? 'name')
const cellType = computed(() => (props.component.cellType as string) ?? 'text')
const updateUrl = computed(() => props.component.updateUrl as string | undefined)
const isDisabled = computed(() => !!props.component.disabled)

type Option = { value: string; label: string }
const options = computed((): Option[] => (props.component.options as Option[]) ?? [])

const displayValue = computed(() => String((props.formData ?? props.record)[displayField.value] ?? '—'))

const editValue = ref(String((props.formData ?? props.record)[editField.value] ?? ''))

const editing = ref(false)
const inputEl = ref<HTMLInputElement | HTMLSelectElement | null>(null)

// Inertia v3 PATCH — instance data becomes the request body.
const http = useHttp<{ field: string; value: string | number | boolean | null }>({
  field: '',
  value: null,
})

function startEdit() {
  if (isDisabled.value) return
  editValue.value = String((props.formData ?? props.record)[editField.value] ?? '')
  editing.value = true
  setTimeout(() => inputEl.value?.focus(), 30)
}

function save() {
  if (http.processing) return

  // Emit up the parent form immediately so the new value is reflected
  // regardless of whether the backend PATCH succeeds.
  emit('update', props.component.name, editValue.value)

  if (!updateUrl.value) {
    editing.value = false
    return
  }

  const url = updateUrl.value.replace(/\{(\w+)\}/g, (_, key) => String((props.record ?? {})[key] ?? ''))

  http.field = editField.value
  http.value = editValue.value

  http.patch(url, {
    onHttpException: (response: { status: number }) => {
      handleHttpException(response)
    },
    onFinish: () => {
      editing.value = false
    },
  })
}

function cancel() {
  editing.value = false
}

function onKeydown(e: KeyboardEvent) {
  if (e.key === 'Enter') save()
  if (e.key === 'Escape') cancel()
}

const displayLabel = computed(() => {
  if (cellType.value !== 'select') return displayValue.value
  return options.value.find((o) => o.value === displayValue.value)?.label ?? displayValue.value
})
</script>

<template>
  <FieldWrapper :component="component" :record="record" :errors="errors" :form-data="formData">
    <template #default>
      <!-- Read mode -->
      <div
        v-if="!editing"
        class="group flex h-8 items-center gap-1.5 rounded-sm border border-transparent px-2.5 transition-colors duration-100"
        :class="isDisabled ? 'text-ink-500 cursor-default' : `hover:border-paper-300 hover:bg-paper-75 cursor-pointer`"
        @click="startEdit"
      >
        <span class="text-ink-800 flex-1 truncate text-sm">{{ displayLabel }}</span>
        <Pencil
          v-if="!isDisabled"
          class="text-ink-300 group-hover:text-ink-500 h-3 w-3 shrink-0 opacity-0 transition-all group-hover:opacity-100"
        />
      </div>

      <!-- Edit mode -->
      <div v-else class="flex items-center gap-1">
        <input
          v-if="cellType !== 'select'"
          ref="inputEl"
          v-model="editValue"
          :type="cellType === 'number' ? 'number' : cellType === 'date' ? 'date' : 'text'"
          :placeholder="(component.placeholder as string) ?? ''"
          class="border-brand-500 bg-card focus:ring-brand-500/20 h-8 flex-1 rounded-sm border px-2.5 text-sm focus:ring-1 focus:outline-none"
          @keydown="onKeydown"
          @blur="save"
        />

        <select
          v-else
          ref="inputEl"
          v-model="editValue"
          class="border-brand-500 bg-card h-8 flex-1 rounded-sm border px-2 text-sm focus:outline-none"
          @change="save"
          @keydown.escape="cancel"
        >
          <option v-for="opt in options" :key="opt.value" :value="opt.value">
            {{ opt.label }}
          </option>
        </select>

        <button
          type="button"
          class="bg-brand-600 hover:bg-brand-700 flex h-7 w-7 shrink-0 items-center justify-center rounded-sm text-white transition-colors disabled:opacity-50"
          :disabled="http.processing"
          @click="save"
        >
          <Check class="h-3.5 w-3.5" />
        </button>

        <button
          type="button"
          class="border-paper-300 text-ink-500 hover:bg-paper-100 flex h-7 w-7 shrink-0 items-center justify-center rounded-sm border transition-colors"
          @click="cancel"
        >
          <X class="h-3.5 w-3.5" />
        </button>
      </div>
    </template>
  </FieldWrapper>
</template>
