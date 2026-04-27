<script setup lang="ts">
import { ref, computed }    from 'vue'
import { VueDraggable }     from 'vue-draggable-plus'
import {
    ChevronDown, ChevronRight,
    Plus, Trash2, GripVertical,
} from 'lucide-vue-next'
import FieldWrapper         from '@/components/darejer/FieldWrapper.vue'
import DarejerComponent     from '@/components/darejer/DarejerComponent.vue'
import useTranslation       from '@/composables/useTranslation'
import type { DarejerComponent as DarejerComponentType } from '@/types/darejer'

const { __ } = useTranslation()

const props = defineProps<{
    component: DarejerComponentType
    record:    Record<string, unknown>
    errors:    Record<string, string>
    formData?: Record<string, unknown>
}>()

const emit = defineEmits<{ (e: 'update', name: string, value: unknown): void }>()

type ItemRow = Record<string, unknown> & { _id: number }

const schema         = computed((): DarejerComponentType[] => (props.component.schema as DarejerComponentType[]) ?? [])
const isAddable      = computed(() => props.component.addable   !== false)
const isDeletable    = computed(() => props.component.deletable !== false)
const isSortable     = computed(() => !!props.component.sortable)
const startCollapsed = computed(() => !!props.component.collapsed)
const maxItems       = computed(() => props.component.maxItems as number | undefined)
const minItems       = computed(() => props.component.minItems as number | undefined)
const addLabel       = computed(() => (props.component.addLabel as string)  ?? __('Add item'))
const itemLabel      = computed(() => (props.component.itemLabel as string) ?? __('Item'))
const itemLabelField = computed(() => props.component.itemLabelField as string | undefined)
const defaultItem    = computed(() => (props.component.defaultItem as Record<string, unknown>) ?? {})

let nextId = 0

const rawValue = (props.formData ?? props.record)[props.component.name]
    ?? props.component.default ?? []

function parseInitial(): ItemRow[] {
    const arr = Array.isArray(rawValue) ? rawValue as Record<string, unknown>[] : []
    return arr.map(row => ({ ...row, _id: nextId++ }))
}

const items     = ref<ItemRow[]>(parseInitial())
const collapsed = ref<Set<number>>(
    startCollapsed.value
        ? new Set(items.value.map(i => i._id))
        : new Set()
)

function emitValue() {
    const cleaned = items.value.map(({ _id, ...rest }) => rest)
    emit('update', props.component.name, cleaned)
}

function headerLabel(item: ItemRow, index: number): string {
    if (itemLabelField.value) {
        const val = String(item[itemLabelField.value] ?? '')
        if (val.trim()) return val
    }
    return `${itemLabel.value} ${index + 1}`
}

function toggleCollapse(id: number) {
    if (collapsed.value.has(id)) {
        collapsed.value.delete(id)
    } else {
        collapsed.value.add(id)
    }
    collapsed.value = new Set(collapsed.value)
}

function addItem() {
    if (maxItems.value && items.value.length >= maxItems.value) return
    const id = nextId++
    const newItem: ItemRow = {
        ...Object.fromEntries(schema.value.map(c => [c.name, c.default ?? ''])),
        ...defaultItem.value,
        _id: id,
    }
    items.value.push(newItem)
    collapsed.value.delete(id)
    collapsed.value = new Set(collapsed.value)
    emitValue()
}

function removeItem(id: number) {
    if (minItems.value && items.value.length <= minItems.value) return
    items.value = items.value.filter(i => i._id !== id)
    emitValue()
}

function onSubUpdate(itemId: number, fieldName: string, value: unknown) {
    const item = items.value.find(i => i._id === itemId)
    if (item) {
        item[fieldName] = value
        emitValue()
    }
}

function itemFormData(item: ItemRow): Record<string, unknown> {
    const { _id, ...rest } = item
    return rest
}

function itemErrors(index: number): Record<string, string> {
    const scoped: Record<string, string> = {}
    const prefix = `${props.component.name}.${index}.`
    for (const [key, val] of Object.entries(props.errors)) {
        if (key.startsWith(prefix)) {
            scoped[key.slice(prefix.length)] = val
        }
    }
    return scoped
}

const atMax = computed(() => maxItems.value ? items.value.length >= maxItems.value : false)
const atMin = computed(() => minItems.value ? items.value.length <= minItems.value : false)
</script>

<template>
    <FieldWrapper
        :component="component"
        :record="record"
        :errors="errors"
        :form-data="formData"
        class="col-span-full"
    >
        <template #default>
            <div class="flex flex-col gap-2">

                <!-- Items -->
                <VueDraggable
                    v-model="items"
                    :disabled="!isSortable"
                    item-key="_id"
                    handle=".repeater-drag-handle"
                    ghost-class="opacity-40"
                    class="flex flex-col gap-2"
                    @end="emitValue"
                >
                    <template #item="{ element: item, index }">
                        <div
                            :key="item._id"
                            class="border border-paper-200 rounded-md overflow-hidden bg-white"
                        >
                            <!-- Item header -->
                            <div
                                class="flex items-center gap-2 px-3 py-2 bg-paper-75 border-b border-paper-200 cursor-pointer select-none"
                                @click="toggleCollapse(item._id)"
                            >
                                <GripVertical
                                    v-if="isSortable"
                                    class="w-3.5 h-3.5 text-ink-300 hover:text-ink-500 cursor-grab repeater-drag-handle shrink-0"
                                    @click.stop
                                />

                                <ChevronDown
                                    v-if="!collapsed.has(item._id)"
                                    class="w-3.5 h-3.5 text-ink-400 shrink-0"
                                />
                                <ChevronRight
                                    v-else
                                    class="w-3.5 h-3.5 text-ink-400 shrink-0"
                                />

                                <span class="flex-1 text-sm font-medium text-ink-700">
                                    {{ headerLabel(item, index) }}
                                </span>

                                <button
                                    v-if="isDeletable && !atMin"
                                    type="button"
                                    class="flex items-center justify-center w-6 h-6 rounded-sm text-ink-300 hover:text-danger-600 hover:bg-danger-50 transition-colors shrink-0"
                                    @click.stop="removeItem(item._id)"
                                >
                                    <Trash2 class="w-3.5 h-3.5" />
                                </button>
                            </div>

                            <!-- Item body -->
                            <div
                                v-if="!collapsed.has(item._id)"
                                class="p-4 bg-white"
                            >
                                <div class="grid grid-cols-2 gap-x-5 gap-y-3.5 w-full">
                                    <DarejerComponent
                                        v-for="subComp in schema"
                                        :key="subComp.name"
                                        :component="subComp"
                                        :record="itemFormData(item)"
                                        :errors="itemErrors(index)"
                                        :form-data="itemFormData(item)"
                                        @update="(name, value) => onSubUpdate(item._id, name, value)"
                                    />
                                </div>
                            </div>
                        </div>
                    </template>
                </VueDraggable>

                <!-- Empty state -->
                <div
                    v-if="items.length === 0"
                    class="flex items-center justify-center py-8 border border-dashed border-paper-300 rounded-md text-sm text-ink-400"
                >
                    {{ __('No items yet. Click ":label" to add one.', { label: addLabel }) }}
                </div>

                <!-- Add button -->
                <button
                    v-if="isAddable && !atMax"
                    type="button"
                    class="flex items-center gap-1.5 h-8 px-3 text-sm text-brand-600 font-medium border border-dashed border-brand-200 rounded-md hover:bg-brand-50 hover:border-brand-300 transition-colors self-start"
                    @click="addItem"
                >
                    <Plus class="w-3.5 h-3.5" />
                    {{ addLabel }}
                </button>

                <!-- Max notice -->
                <p v-if="atMax && maxItems" class="text-xs text-ink-400">
                    {{ __('Maximum :max items reached.', { max: maxItems }) }}
                </p>

            </div>
        </template>
    </FieldWrapper>
</template>
