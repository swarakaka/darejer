<script setup lang="ts">
import { ref }          from 'vue'
import { Checkbox }     from '@/components/ui/checkbox'
import FieldWrapper     from '@/components/darejer/FieldWrapper.vue'
import type { DarejerComponent } from '@/types/darejer'

const props = defineProps<{
    component: DarejerComponent
    record:    Record<string, unknown>
    errors:    Record<string, string>
    formData?: Record<string, unknown>
}>()

const emit = defineEmits<{ (e: 'update', name: string, value: unknown): void }>()

const checked = ref(Boolean(
    (props.formData ?? props.record)[props.component.name]
    ?? props.component.default ?? false
))

function onChange(val: boolean | 'indeterminate') {
    const v = val === 'indeterminate' ? false : val
    checked.value = v
    emit('update', props.component.name, v)
}
</script>

<template>
    <FieldWrapper :component="component" :record="record" :errors="errors" :form-data="formData">
        <template #default="{ hasError }">
            <div class="flex items-center gap-2 h-[2.125rem]">
                <Checkbox
                    :id="component.name"
                    :model-value="checked"
                    :disabled="(component.disabled as boolean)"
                    :class="hasError ? 'border-danger-600' : ''"
                    @update:model-value="onChange"
                />
                <label
                    v-if="component.checkboxLabel"
                    :for="component.name"
                    class="text-sm text-ink-700 cursor-pointer select-none"
                >
                    {{ component.checkboxLabel }}
                </label>
            </div>
        </template>
    </FieldWrapper>
</template>
