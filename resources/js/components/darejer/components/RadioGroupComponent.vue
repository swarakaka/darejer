<script setup lang="ts">
import { ref }          from 'vue'
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group'
import FieldWrapper     from '@/components/darejer/FieldWrapper.vue'
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
        <template #default>
            <RadioGroup
                :model-value="current"
                :disabled="(component.disabled as boolean)"
                :class="(component.layout as string) === 'horizontal'
                    ? 'flex flex-row flex-wrap gap-4'
                    : 'flex flex-col gap-1.5'"
                @update:model-value="onChange"
            >
                <div
                    v-for="option in (component.options as Option[])"
                    :key="option.value"
                    class="flex items-center gap-1.5"
                >
                    <RadioGroupItem
                        :id="`${component.name}-${option.value}`"
                        :value="option.value"
                    />
                    <label
                        :for="`${component.name}-${option.value}`"
                        class="text-sm text-ink-700 cursor-pointer select-none"
                    >
                        {{ option.label }}
                    </label>
                </div>
            </RadioGroup>
        </template>
    </FieldWrapper>
</template>
