<script setup lang="ts">
import { computed } from 'vue'
import BreadcrumbsComponentVue from '@/components/darejer/components/BreadcrumbsComponentVue.vue'
import CheckboxComponent from '@/components/darejer/components/CheckboxComponent.vue'
import CheckboxListComponent from '@/components/darejer/components/CheckboxListComponent.vue'
import ComboboxComponent from '@/components/darejer/components/ComboboxComponent.vue'
import DataGridComponent from '@/components/darejer/components/DataGridComponent.vue'
import DatePickerComponent from '@/components/darejer/components/DatePickerComponent.vue'
import DiagramComponent from '@/components/darejer/components/DiagramComponent.vue'
import DisplayComponent from '@/components/darejer/components/DisplayComponent.vue'
import EditableTableComponent from '@/components/darejer/components/EditableTableComponent.vue'
import FileUploadComponent from '@/components/darejer/components/FileUploadComponent.vue'
import FilterPanelComponent from '@/components/darejer/components/FilterPanelComponent.vue'
import GanttComponent from '@/components/darejer/components/GanttComponent.vue'
import IconComponent from '@/components/darejer/components/IconComponent.vue'
import InPlaceEditorComponent from '@/components/darejer/components/InPlaceEditorComponent.vue'
import KanbanComponent from '@/components/darejer/components/KanbanComponent.vue'
import KeyValueEditorComponent from '@/components/darejer/components/KeyValueEditorComponent.vue'
import MoneyComponent from '@/components/darejer/components/MoneyComponent.vue'
import NavigationComponent from '@/components/darejer/components/NavigationComponent.vue'
import PaginationComponent from '@/components/darejer/components/PaginationComponent.vue'
import PDFViewerComponent from '@/components/darejer/components/PDFViewerComponent.vue'
import RadioGroupComponent from '@/components/darejer/components/RadioGroupComponent.vue'
import RepeaterComponent from '@/components/darejer/components/RepeaterComponent.vue'
import RichTextEditorComponent from '@/components/darejer/components/RichTextEditorComponent.vue'
import SchedulerComponent from '@/components/darejer/components/SchedulerComponent.vue'
import SelectComponent from '@/components/darejer/components/SelectComponent.vue'
import SignatureComponent from '@/components/darejer/components/SignatureComponent.vue'
import TableComponent from '@/components/darejer/components/TableComponent.vue'
import TagsInputComponent from '@/components/darejer/components/TagsInputComponent.vue'
import TextareaComponent from '@/components/darejer/components/TextareaComponent.vue'
import TextInputComponent from '@/components/darejer/components/TextInputComponent.vue'
import TimePickerComponent from '@/components/darejer/components/TimePickerComponent.vue'
import ToggleComponent from '@/components/darejer/components/ToggleComponent.vue'
import TooltipComponentVue from '@/components/darejer/components/TooltipComponentVue.vue'
import TranslatableInputComponent from '@/components/darejer/components/TranslatableInputComponent.vue'
import TranslatableTextareaComponent from '@/components/darejer/components/TranslatableTextareaComponent.vue'
import TreeGridComponent from '@/components/darejer/components/TreeGridComponent.vue'
import { evaluateDependOn } from '@/composables/useDependOn'
import useTranslation from '@/composables/useTranslation'
import type { DarejerComponent as DarejerComponentType } from '@/types/darejer'

const props = defineProps<{
  component: DarejerComponentType
  record: Record<string, unknown>
  errors: Record<string, string>
  formData?: Record<string, unknown>
}>()

const emit = defineEmits<{
  (e: 'update', name: string, value: unknown): void
  (e: 'prefill', fields: Record<string, unknown>): void
}>()

const { __ } = useTranslation()

const componentMap: Record<string, unknown> = {
  TextInput: TextInputComponent,
  Money: MoneyComponent,
  Textarea: TextareaComponent,
  Toggle: ToggleComponent,
  Checkbox: CheckboxComponent,
  CheckboxList: CheckboxListComponent,
  RadioGroup: RadioGroupComponent,
  Select: SelectComponent,
  TranslatableInput: TranslatableInputComponent,
  TranslatableTextarea: TranslatableTextareaComponent,
  Combobox: ComboboxComponent,
  TagsInput: TagsInputComponent,
  KeyValueEditor: KeyValueEditorComponent,
  DatePicker: DatePickerComponent,
  TimePicker: TimePickerComponent,
  FileUpload: FileUploadComponent,
  RichTextEditor: RichTextEditorComponent,
  Signature: SignatureComponent,
  DataGrid: DataGridComponent,
  FilterPanel: FilterPanelComponent,
  Pagination: PaginationComponent,
  Kanban: KanbanComponent,
  TreeGrid: TreeGridComponent,
  InPlaceEditor: InPlaceEditorComponent,
  EditableTable: EditableTableComponent,
  Table: TableComponent,
  Repeater: RepeaterComponent,
  PDFViewer: PDFViewerComponent,
  Diagram: DiagramComponent,
  Scheduler: SchedulerComponent,
  Gantt: GanttComponent,
  Icon: IconComponent,
  TooltipComponent: TooltipComponentVue,
  BreadcrumbsComponent: BreadcrumbsComponentVue,
  Navigation: NavigationComponent,
  Display: DisplayComponent,
}

const resolvedComponent = computed(() => componentMap[props.component.type] ?? null)

// Evaluate dependOn at the wrapper level so a hidden field collapses the
// grid cell entirely. Without this, the inner FieldWrapper's v-if removes
// the field but the outer grid cell remains, leaving an empty gap.
const isVisible = computed(() => evaluateDependOn(props.component.dependOn, props.formData ?? props.record))
</script>

<template>
  <template v-if="isVisible">
    <div v-if="resolvedComponent" :class="{ 'col-span-full': component.fullWidth }">
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
    <div v-else class="border-warning-500 bg-warning-50 text-warning-600 rounded border px-2.5 py-1.5 text-xs">
      {{ __('Unknown component type:') }} <strong>{{ component.type }}</strong>
    </div>
  </template>
</template>
