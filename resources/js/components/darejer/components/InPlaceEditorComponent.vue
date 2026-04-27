<script setup lang="ts">
import { ref, computed } from 'vue'
import { useHttp }       from '@inertiajs/vue3'
import { Check, X, Pencil } from 'lucide-vue-next'
import FieldWrapper from '@/components/darejer/FieldWrapper.vue'
import type { DarejerComponent } from '@/types/darejer'

const props = defineProps<{
    component: DarejerComponent
    record:    Record<string, unknown>
    errors:    Record<string, string>
    formData?: Record<string, unknown>
}>()

const emit = defineEmits<{ (e: 'update', name: string, value: unknown): void }>()

const displayField = computed(() => (props.component.displayField as string) ?? 'name')
const editField    = computed(() => (props.component.editField    as string) ?? 'name')
const cellType     = computed(() => (props.component.cellType     as string) ?? 'text')
const updateUrl    = computed(() => props.component.updateUrl as string | undefined)
const isDisabled   = computed(() => !!props.component.disabled)

type Option = { value: string; label: string }
const options = computed((): Option[] => (props.component.options as Option[]) ?? [])

const displayValue = computed(() =>
    String((props.formData ?? props.record)[displayField.value] ?? '—')
)

const editValue = ref(
    String((props.formData ?? props.record)[editField.value] ?? '')
)

const editing = ref(false)
const inputEl = ref<HTMLInputElement | HTMLSelectElement | null>(null)

// Inertia v3 PATCH — instance data becomes the request body.
const http = useHttp<{ field: string; value: string | number | boolean | null }>({ field: '', value: null })

function startEdit() {
    if (isDisabled.value) return
    editValue.value = String((props.formData ?? props.record)[editField.value] ?? '')
    editing.value   = true
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

    const url = updateUrl.value.replace(
        /\{(\w+)\}/g,
        (_, key) => String((props.record ?? {})[key] ?? '')
    )

    http.field = editField.value
    http.value = editValue.value

    http.patch(url, {
        onFinish: () => {
            editing.value = false
        },
    })
}

function cancel() {
    editing.value = false
}

function onKeydown(e: KeyboardEvent) {
    if (e.key === 'Enter')  save()
    if (e.key === 'Escape') cancel()
}

const displayLabel = computed(() => {
    if (cellType.value !== 'select') return displayValue.value
    return options.value.find(o => o.value === displayValue.value)?.label ?? displayValue.value
})
</script>

<template>
    <FieldWrapper :component="component" :record="record" :errors="errors" :form-data="formData">
        <template #default>

            <!-- Read mode -->
            <div
                v-if="!editing"
                class="group flex items-center gap-1.5 h-8 px-2.5 rounded-sm border border-transparent transition-colors duration-100"
                :class="isDisabled
                    ? 'cursor-default text-ink-500'
                    : 'cursor-pointer hover:border-paper-300 hover:bg-paper-75'"
                @click="startEdit"
            >
                <span class="flex-1 text-sm text-ink-800 truncate">{{ displayLabel }}</span>
                <Pencil
                    v-if="!isDisabled"
                    class="w-3 h-3 text-ink-300 group-hover:text-ink-500 opacity-0 group-hover:opacity-100 transition-all shrink-0"
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
                    class="flex-1 h-8 px-2.5 text-sm border border-brand-500 rounded-sm bg-white
                           focus:outline-none focus:ring-1 focus:ring-brand-500/20"
                    @keydown="onKeydown"
                    @blur="save"
                />

                <select
                    v-else
                    ref="inputEl"
                    v-model="editValue"
                    class="flex-1 h-8 px-2 text-sm border border-brand-500 rounded-sm bg-white focus:outline-none"
                    @change="save"
                    @keydown.escape="cancel"
                >
                    <option v-for="opt in options" :key="opt.value" :value="opt.value">
                        {{ opt.label }}
                    </option>
                </select>

                <button
                    type="button"
                    class="flex items-center justify-center w-7 h-7 rounded-sm bg-brand-600 text-white hover:bg-brand-700 transition-colors shrink-0 disabled:opacity-50"
                    :disabled="http.processing"
                    @click="save"
                >
                    <Check class="w-3.5 h-3.5" />
                </button>

                <button
                    type="button"
                    class="flex items-center justify-center w-7 h-7 rounded-sm border border-paper-300 text-ink-500 hover:bg-paper-100 transition-colors shrink-0"
                    @click="cancel"
                >
                    <X class="w-3.5 h-3.5" />
                </button>

            </div>

        </template>
    </FieldWrapper>
</template>
