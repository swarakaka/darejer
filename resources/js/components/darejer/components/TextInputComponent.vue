<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { Input }    from '@/components/ui/input'
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
    const val = (e.target as HTMLInputElement).value
    value.value = val
    emit('update', props.component.name, val)
}

const prefixEl    = ref<HTMLElement | null>(null)
const suffixEl    = ref<HTMLElement | null>(null)
const prefixWidth = ref(0)
const suffixWidth = ref(0)

onMounted(() => {
    if (prefixEl.value) prefixWidth.value = prefixEl.value.offsetWidth
    if (suffixEl.value) suffixWidth.value = suffixEl.value.offsetWidth
})

const inputPaddingLeft = computed(() =>
    prefixWidth.value > 0 ? `${prefixWidth.value + 6}px` : undefined
)
const inputPaddingRight = computed(() =>
    suffixWidth.value > 0 ? `${suffixWidth.value + 6}px` : undefined
)
</script>

<template>
    <FieldWrapper :component="component" :record="record" :errors="errors" :form-data="formData">
        <template #default="{ hasError }">
            <div class="relative flex items-center w-full">

                <!-- Prefix -->
                <span
                    v-if="component.prefix"
                    ref="prefixEl"
                    class="absolute start-0 flex items-center h-full px-2 text-sm
                           text-ink-500 border-e border-paper-300 bg-paper-75
                           rounded-s-sm pointer-events-none select-none whitespace-nowrap z-10"
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
                    :class="hasError ? 'border-danger-600' : ''"
                    :style="{
                        paddingLeft:  inputPaddingLeft,
                        paddingRight: inputPaddingRight,
                    }"
                    @input="onInput"
                />

                <!-- Suffix -->
                <span
                    v-if="component.suffix"
                    ref="suffixEl"
                    class="absolute end-0 flex items-center h-full px-2 text-sm
                           text-ink-500 border-s border-paper-300 bg-paper-75
                           rounded-e-sm pointer-events-none select-none whitespace-nowrap z-10"
                >
                    {{ component.suffix }}
                </span>

            </div>
        </template>
    </FieldWrapper>
</template>
