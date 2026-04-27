<script setup lang="ts">
import { ref }   from 'vue'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import FieldWrapper from '@/components/darejer/FieldWrapper.vue'
import type { DarejerComponent } from '@/types/darejer'

const props = defineProps<{
    component: DarejerComponent
    record:    Record<string, unknown>
    errors:    Record<string, string>
    formData?: Record<string, unknown>
}>()

const emit = defineEmits<{ (e: 'update', name: string, value: unknown): void }>()

type Option = { value: string; label: string }

const current = ref(String(
    (props.formData ?? props.record)[props.component.name]
    ?? props.component.default ?? ''
))

function onChange(val: unknown) {
    const v = val == null ? '' : String(val)
    current.value = v
    emit('update', props.component.name, v)
}
</script>

<template>
    <FieldWrapper :component="component" :record="record" :errors="errors" :form-data="formData">
        <template #default="{ hasError }">
            <Select
                :model-value="current"
                :disabled="(component.disabled as boolean)"
                @update:model-value="onChange"
            >
                <SelectTrigger
                    :id="component.name"
                    class="w-full text-sm"
                    :class="hasError ? 'border-danger-600' : ''"
                >
                    <SelectValue :placeholder="(component.placeholder as string) ?? 'Select…'" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="option in (component.options as Option[])"
                        :key="option.value"
                        :value="option.value"
                        class="text-sm"
                    >
                        {{ option.label }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </template>
    </FieldWrapper>
</template>
