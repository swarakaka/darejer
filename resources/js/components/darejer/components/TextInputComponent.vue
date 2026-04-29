<script setup lang="ts">
import { ref, computed }   from 'vue'
import { Input }           from '@/components/ui/input'
import FieldWrapper        from '@/components/darejer/FieldWrapper.vue'
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
    const val = (e.target as HTMLInputElement).value
    value.value = val
    emit('update', props.component.name, val)
}

const hasPrefix = computed(() => !!props.component.prefix)
const hasSuffix = computed(() => !!props.component.suffix)
</script>

<template>
    <FieldWrapper :component="component" :record="record" :errors="errors" :form-data="formData">
        <template #default="{ hasError }">
            <div class="relative flex items-center w-full">

                <!-- Prefix -->
                <span
                    v-if="hasPrefix"
                    class="absolute inset-y-0 inset-s-0 flex items-center px-2.5 text-sm
                           text-ink-500 border-e border-paper-300 bg-paper-50
                           rounded-s-md pointer-events-none select-none whitespace-nowrap z-10
                           max-w-[40%] truncate"
                >
                    {{ component.prefix }}
                </span>

                <Input
                    :id="component.name"
                    :name="component.name"
                    :type="(component.inputType as string) ?? 'text'"
                    :placeholder="(component.placeholder as string) ?? ''"
                    :value="value as string"
                    :readonly="(component.readonly as boolean)"
                    :disabled="(component.disabled as boolean)"
                    :maxlength="(component.maxLength as number)"
                    :autofocus="(component.autofocus as boolean)"
                    class="w-full"
                    :class="[
                        hasError ? 'border-danger-600' : '',
                        hasPrefix ? 'ps-20' : '',
                        hasSuffix ? 'pe-20' : '',
                    ]"
                    @input="onInput"
                />

                <!-- Suffix -->
                <span
                    v-if="hasSuffix"
                    class="absolute inset-y-0 inset-e-0 flex items-center px-2.5 text-sm
                           text-ink-500 border-s border-paper-300 bg-paper-50
                           rounded-e-md pointer-events-none select-none whitespace-nowrap z-10
                           max-w-[40%] truncate"
                >
                    {{ component.suffix }}
                </span>

            </div>
        </template>
    </FieldWrapper>
</template>
