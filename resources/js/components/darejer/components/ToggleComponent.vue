<script setup lang="ts">
import { ref } from 'vue'
import FieldWrapper from '@/components/darejer/FieldWrapper.vue'
import { Switch } from '@/components/ui/switch'
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

const checked = ref(Boolean((props.formData ?? props.record)[props.component.name] ?? props.component.default ?? false))

function onChange(val: boolean) {
  checked.value = val
  emit('update', props.component.name, val)
}
</script>

<template>
  <FieldWrapper :component="component" :record="record" :errors="errors" :form-data="formData">
    <template #default>
      <div class="flex h-8.5 items-center gap-2">
        <Switch
          :id="component.name"
          :model-value="checked"
          :disabled="component.disabled as boolean"
          @update:model-value="onChange"
        />
        <label :for="component.name" class="text-ink-700 cursor-pointer text-sm select-none">
          {{ checked ? ((component.onLabel as string) ?? __('Yes')) : ((component.offLabel as string) ?? __('No')) }}
        </label>
      </div>
    </template>
  </FieldWrapper>
</template>
