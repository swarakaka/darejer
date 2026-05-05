<script setup lang="ts">
import { ref, computed } from 'vue'
import { X } from 'lucide-vue-next'
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

const rawValue =
  (props.formData ?? props.record)[props.component.name] ?? props.component.default ?? []

const tags = ref<string[]>(Array.isArray(rawValue) ? rawValue.map(String) : [])

const input = ref('')
const inputEl = ref<HTMLInputElement | null>(null)
const showSuggest = ref(false)

const suggestions = computed(() => (props.component.suggestions as string[] | undefined) ?? [])

const freeform = computed(() => props.component.freeform !== false)

const maxTags = computed(() => props.component.max as number | undefined)

const filtered = computed(() =>
  suggestions.value.filter(
    (s) => s.toLowerCase().includes(input.value.toLowerCase()) && !tags.value.includes(s),
  ),
)

function addTag(tag: string) {
  const trimmed = tag.trim()
  if (!trimmed) return
  if (tags.value.includes(trimmed)) return
  if (maxTags.value && tags.value.length >= maxTags.value) return
  if (!freeform.value && !suggestions.value.includes(trimmed)) return

  tags.value = [...tags.value, trimmed]
  input.value = ''
  showSuggest.value = false
  emit('update', props.component.name, tags.value)
}

function removeTag(index: number) {
  tags.value = tags.value.filter((_, i) => i !== index)
  emit('update', props.component.name, tags.value)
}

function onKeydown(e: KeyboardEvent) {
  if (e.key === 'Enter' || e.key === ',') {
    e.preventDefault()
    addTag(input.value)
  } else if (e.key === 'Backspace' && input.value === '' && tags.value.length > 0) {
    removeTag(tags.value.length - 1)
  }
}

function onBlur() {
  setTimeout(() => {
    showSuggest.value = false
  }, 150)
  if (input.value && freeform.value) addTag(input.value)
}

const atMax = computed(() => (maxTags.value ? tags.value.length >= maxTags.value : false))
</script>

<template>
  <FieldWrapper :component="component" :record="record" :errors="errors" :form-data="formData">
    <template #default="{ hasError }">
      <div class="relative">
        <!-- Tag container -->
        <div
          class="flex min-h-[2.125rem] cursor-text flex-wrap items-center gap-1 rounded border bg-card px-2 py-1 transition-colors duration-100 focus-within:border-brand-500 focus-within:ring-1 focus-within:ring-brand-500/20"
          :class="hasError ? 'border-danger-600' : 'border-slate-300'"
          @click="inputEl?.focus()"
        >
          <!-- Tags -->
          <span
            v-for="(tag, i) in tags"
            :key="tag"
            class="inline-flex h-5 items-center gap-1 rounded border border-brand-200 bg-brand-50 px-1.5 text-xs font-medium text-brand-700"
          >
            {{ tag }}
            <button
              type="button"
              class="text-brand-400 transition-colors hover:text-brand-700"
              :disabled="component.disabled as boolean"
              @click.stop="removeTag(i)"
            >
              <X class="h-2.5 w-2.5" />
            </button>
          </span>

          <!-- Input -->
          <input
            v-if="!atMax"
            ref="inputEl"
            v-model="input"
            type="text"
            :placeholder="
              tags.length === 0 ? ((component.placeholder as string) ?? __('Add tags…')) : ''
            "
            :disabled="component.disabled as boolean"
            class="h-5 min-w-[8rem] flex-1 border-none bg-transparent text-sm outline-none placeholder:text-slate-400"
            @keydown="onKeydown"
            @focus="showSuggest = true"
            @blur="onBlur"
          />
        </div>

        <!-- Suggestions dropdown -->
        <div
          v-if="showSuggest && filtered.length > 0"
          class="absolute start-0 end-0 top-full z-50 mt-1 overflow-hidden rounded border border-slate-200 bg-card"
        >
          <button
            v-for="s in filtered.slice(0, 8)"
            :key="s"
            type="button"
            class="flex h-8 w-full items-center px-3 text-start text-sm transition-colors hover:bg-slate-50"
            @mousedown.prevent="addTag(s)"
          >
            {{ s }}
          </button>
        </div>

        <!-- Max reached hint -->
        <p v-if="atMax" class="mt-1 text-xs text-slate-400">
          {{ __('Maximum :max tags reached.', { max: maxTags ?? 0 }) }}
        </p>
      </div>
    </template>
  </FieldWrapper>
</template>
