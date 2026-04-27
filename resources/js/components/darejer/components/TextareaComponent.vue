<script setup lang="ts">
import { ref }      from 'vue'
import { Textarea } from '@/components/ui/textarea'
import FieldWrapper from '@/components/darejer/FieldWrapper.vue'
import type { DarejerComponent } from '@/types/darejer'

const props = defineProps<{
    component: DarejerComponent
    record:    Record<string, unknown>
    errors:    Record<string, string>
    formData?: Record<string, unknown>
}>()

const emit = defineEmits<{ (e: 'update', name: string, value: unknown): void }>()

const value = ref(
    (props.formData ?? props.record)[props.component.name]
    ?? props.component.default ?? ''
)

function onInput(e: Event) {
    const val = (e.target as HTMLTextAreaElement).value
    value.value = val
    emit('update', props.component.name, val)
}
</script>

<template>
    <FieldWrapper :component="component" :record="record" :errors="errors" :form-data="formData">
        <template #default="{ hasError }">
            <Textarea
                :id="component.name"
                :name="component.name"
                :placeholder="(component.placeholder as string) ?? ''"
                :rows="(component.rows as number) ?? 4"
                :readonly="(component.readonly as boolean)"
                :disabled="(component.disabled as boolean)"
                :value="value as string"
                class="w-full text-sm resize-y"
                :class="hasError ? 'border-danger-600' : ''"
                @input="onInput"
            />
        </template>
    </FieldWrapper>
</template>
