<script setup lang="ts">
import { computed }             from 'vue'
import type { DarejerComponent as DarejerComponentType } from '@/types/darejer'
import useTranslation           from '@/composables/useTranslation'

import TextInputComponent             from '@/components/darejer/components/TextInputComponent.vue'
import TextareaComponent              from '@/components/darejer/components/TextareaComponent.vue'
import ToggleComponent                from '@/components/darejer/components/ToggleComponent.vue'
import CheckboxComponent              from '@/components/darejer/components/CheckboxComponent.vue'
import RadioGroupComponent            from '@/components/darejer/components/RadioGroupComponent.vue'
import SelectComponent                from '@/components/darejer/components/SelectComponent.vue'
import TranslatableInputComponent     from '@/components/darejer/components/TranslatableInputComponent.vue'
import TranslatableTextareaComponent  from '@/components/darejer/components/TranslatableTextareaComponent.vue'
import ComboboxComponent              from '@/components/darejer/components/ComboboxComponent.vue'
import TagsInputComponent             from '@/components/darejer/components/TagsInputComponent.vue'
import KeyValueEditorComponent        from '@/components/darejer/components/KeyValueEditorComponent.vue'
import DatePickerComponent            from '@/components/darejer/components/DatePickerComponent.vue'
import TimePickerComponent            from '@/components/darejer/components/TimePickerComponent.vue'
import FileUploadComponent            from '@/components/darejer/components/FileUploadComponent.vue'
import RichTextEditorComponent        from '@/components/darejer/components/RichTextEditorComponent.vue'
import SignatureComponent             from '@/components/darejer/components/SignatureComponent.vue'
import DataGridComponent              from '@/components/darejer/components/DataGridComponent.vue'
import FilterPanelComponent           from '@/components/darejer/components/FilterPanelComponent.vue'
import PaginationComponent            from '@/components/darejer/components/PaginationComponent.vue'
import KanbanComponent                from '@/components/darejer/components/KanbanComponent.vue'
import TreeGridComponent              from '@/components/darejer/components/TreeGridComponent.vue'
import InPlaceEditorComponent         from '@/components/darejer/components/InPlaceEditorComponent.vue'
import EditableTableComponent         from '@/components/darejer/components/EditableTableComponent.vue'
import RepeaterComponent              from '@/components/darejer/components/RepeaterComponent.vue'
import PDFViewerComponent             from '@/components/darejer/components/PDFViewerComponent.vue'
import DiagramComponent               from '@/components/darejer/components/DiagramComponent.vue'
import SchedulerComponent             from '@/components/darejer/components/SchedulerComponent.vue'
import GanttComponent                 from '@/components/darejer/components/GanttComponent.vue'
import IconComponent                  from '@/components/darejer/components/IconComponent.vue'
import TooltipComponentVue            from '@/components/darejer/components/TooltipComponentVue.vue'
import BreadcrumbsComponentVue        from '@/components/darejer/components/BreadcrumbsComponentVue.vue'
import NavigationComponent            from '@/components/darejer/components/NavigationComponent.vue'

const props = defineProps<{
    component: DarejerComponentType
    record:    Record<string, unknown>
    errors:    Record<string, string>
    formData?: Record<string, unknown>
}>()

const emit = defineEmits<{
    (e: 'update', name: string, value: unknown): void
    (e: 'prefill', fields: Record<string, unknown>): void
}>()

const { __ } = useTranslation()

const componentMap: Record<string, unknown> = {
    TextInput:             TextInputComponent,
    Textarea:              TextareaComponent,
    Toggle:                ToggleComponent,
    Checkbox:              CheckboxComponent,
    RadioGroup:            RadioGroupComponent,
    Select:                SelectComponent,
    TranslatableInput:     TranslatableInputComponent,
    TranslatableTextarea:  TranslatableTextareaComponent,
    Combobox:              ComboboxComponent,
    TagsInput:             TagsInputComponent,
    KeyValueEditor:        KeyValueEditorComponent,
    DatePicker:            DatePickerComponent,
    TimePicker:            TimePickerComponent,
    FileUpload:            FileUploadComponent,
    RichTextEditor:        RichTextEditorComponent,
    Signature:             SignatureComponent,
    DataGrid:              DataGridComponent,
    FilterPanel:           FilterPanelComponent,
    Pagination:            PaginationComponent,
    Kanban:                KanbanComponent,
    TreeGrid:              TreeGridComponent,
    InPlaceEditor:         InPlaceEditorComponent,
    EditableTable:         EditableTableComponent,
    Repeater:              RepeaterComponent,
    PDFViewer:             PDFViewerComponent,
    Diagram:               DiagramComponent,
    Scheduler:             SchedulerComponent,
    Gantt:                 GanttComponent,
    Icon:                  IconComponent,
    TooltipComponent:      TooltipComponentVue,
    BreadcrumbsComponent:  BreadcrumbsComponentVue,
    Navigation:            NavigationComponent,
}

const resolvedComponent = computed(() => componentMap[props.component.type] ?? null)
</script>

<template>
    <div
        v-if="resolvedComponent"
        :class="{ 'col-span-full': component.fullWidth }"
    >
        <component
            :is="resolvedComponent"
            :component="component"
            :record="record"
            :errors="errors"
            :form-data="formData"
            @update="(name: string, value: unknown) => emit('update', name, value)"
            @prefill="(fields: Record<string, unknown>) => emit('prefill', fields)"
        />
    </div>
    <div
        v-else
        class="px-2.5 py-1.5 rounded text-xs border border-warning-500 bg-warning-50 text-warning-600"
    >
        {{ __('Unknown component type:') }} <strong>{{ component.type }}</strong>
    </div>
</template>
