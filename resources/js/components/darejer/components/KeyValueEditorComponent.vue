<script setup lang="ts">
import { ref }          from 'vue'
import { Input }        from '@/components/ui/input'
import { Plus, Trash2 } from 'lucide-vue-next'
import FieldWrapper     from '@/components/darejer/FieldWrapper.vue'
import useTranslation   from '@/composables/useTranslation'
import type { DarejerComponent } from '@/types/darejer'

const { __ } = useTranslation()

const props = defineProps<{
    component: DarejerComponent
    record:    Record<string, unknown>
    errors:    Record<string, string>
    formData?: Record<string, unknown>
}>()

const emit = defineEmits<{ (e: 'update', name: string, value: unknown): void }>()

type Row = { key: string; value: string; id: number }

let nextId = 0

function parseInitial(): Row[] {
    const raw = (props.formData ?? props.record)[props.component.name]
        ?? props.component.default ?? {}

    if (typeof raw === 'object' && raw !== null && !Array.isArray(raw)) {
        return Object.entries(raw as Record<string, string>).map(([k, v]) => ({
            key: k, value: v, id: nextId++,
        }))
    }

    return [{ key: '', value: '', id: nextId++ }]
}

const rows = ref<Row[]>(parseInitial())

function emitValue() {
    const obj = Object.fromEntries(
        rows.value
            .filter(r => r.key.trim() !== '')
            .map(r => [r.key.trim(), r.value])
    )
    emit('update', props.component.name, obj)
}

function addRow() {
    const max = props.component.max as number | undefined
    if (max && rows.value.length >= max) return
    rows.value.push({ key: '', value: '', id: nextId++ })
}

function removeRow(id: number) {
    rows.value = rows.value.filter(r => r.id !== id)
    emitValue()
}

function onKeyInput(id: number, e: Event) {
    const row = rows.value.find(r => r.id === id)
    if (row) {
        row.key = (e.target as HTMLInputElement).value
        emitValue()
    }
}

function onValueInput(id: number, e: Event) {
    const row = rows.value.find(r => r.id === id)
    if (row) {
        row.value = (e.target as HTMLInputElement).value
        emitValue()
    }
}

const keyLabel   = (props.component.keyLabel   as string) ?? __('Key')
const valueLabel = (props.component.valueLabel as string) ?? __('Value')
</script>

<template>
    <FieldWrapper :component="component" :record="record" :errors="errors" :form-data="formData">
        <template #default="{ hasError }">
            <div
                class="border rounded overflow-hidden"
                :class="hasError ? 'border-danger-600' : 'border-slate-200'"
            >
                <!-- Header -->
                <div class="grid grid-cols-[1fr_1fr_2rem] bg-slate-75 border-b border-slate-200">
                    <div class="px-2.5 py-1.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">
                        {{ keyLabel }}
                    </div>
                    <div class="px-2.5 py-1.5 text-xs font-semibold text-slate-500 uppercase tracking-wide border-l border-slate-200">
                        {{ valueLabel }}
                    </div>
                    <div />
                </div>

                <!-- Rows -->
                <div
                    v-for="row in rows"
                    :key="row.id"
                    class="grid grid-cols-[1fr_1fr_2rem] border-b border-slate-200 last:border-b-0"
                >
                    <div class="flex items-center">
                        <Input
                            :value="row.key"
                            :disabled="(component.disabled as boolean)"
                            :placeholder="__('key')"
                            class="h-8 border-none rounded-none text-sm font-mono focus:ring-0 focus:border-none"
                            @input="onKeyInput(row.id, $event)"
                        />
                    </div>
                    <div class="flex items-center border-l border-slate-200">
                        <Input
                            :value="row.value"
                            :disabled="(component.disabled as boolean)"
                            :placeholder="__('value')"
                            class="h-8 border-none rounded-none text-sm focus:ring-0 focus:border-none"
                            @input="onValueInput(row.id, $event)"
                        />
                    </div>
                    <div class="flex items-center justify-center border-l border-slate-200">
                        <button
                            type="button"
                            :disabled="(component.disabled as boolean)"
                            class="flex items-center justify-center w-8 h-8
                                   text-slate-300 hover:text-danger-600 transition-colors duration-100
                                   disabled:opacity-40"
                            @click="removeRow(row.id)"
                        >
                            <Trash2 class="w-3.5 h-3.5" />
                        </button>
                    </div>
                </div>

                <!-- Add row button -->
                <div
                    v-if="!component.disabled"
                    class="flex items-center px-2 py-1.5 border-t border-slate-200 bg-slate-75"
                >
                    <button
                        type="button"
                        class="flex items-center gap-1.5 text-xs text-brand-600 font-medium
                               hover:text-brand-700 transition-colors duration-100"
                        @click="addRow"
                    >
                        <Plus class="w-3 h-3" />
                        {{ __('Add row') }}
                    </button>
                </div>
            </div>
        </template>
    </FieldWrapper>
</template>
