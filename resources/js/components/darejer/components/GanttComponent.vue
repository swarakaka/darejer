<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useHttp }      from '@inertiajs/vue3'
import { Loader2 }      from 'lucide-vue-next'
import FieldWrapper     from '@/components/darejer/FieldWrapper.vue'
import type { DarejerComponent } from '@/types/darejer'

// dhtmlx-gantt ships as a global singleton.
import { gantt } from 'dhtmlx-gantt'
import 'dhtmlx-gantt/codebase/dhtmlxgantt.css'

const props = defineProps<{
    component: DarejerComponent
    record:    Record<string, unknown>
    errors:    Record<string, string>
    formData?: Record<string, unknown>
}>()

const isReadonly    = computed(() => !!props.component.readonly)
const height        = computed(() => (props.component.height as number) ?? 500)
const scale         = computed(() => (props.component.scale  as string) ?? 'week')
const showProgress  = computed(() => props.component.progress !== false)
const textField     = computed(() => (props.component.textField     as string) ?? 'text')
const startField    = computed(() => (props.component.startField    as string) ?? 'start_date')
const endField      = computed(() => (props.component.endField      as string) ?? 'end_date')
const durationField = computed(() => (props.component.durationField as string) ?? 'duration')
const progressField = computed(() => (props.component.progressField as string) ?? 'progress')

const containerEl = ref<HTMLElement | null>(null)
const http        = useHttp<Record<string, never>, {
    data?:  Record<string, unknown>[]
    tasks?: Record<string, unknown>[]
    links?: Record<string, unknown>[]
}>()
let initialized = false

function configureGantt() {
    gantt.config.columns = [
        {
            name:  'text',
            label: 'Task',
            tree:  true,
            width: 200,
            template: (task: Record<string, unknown>) =>
                String(task[textField.value] ?? task.text ?? ''),
        },
        {
            name:  'start_date',
            label: 'Start',
            align: 'center',
            width: 90,
            template: (task: Record<string, unknown>) => {
                const val = task[startField.value] ?? task.start_date
                return val
                    ? new Date(String(val)).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
                    : ''
            },
        },
        {
            name:  'duration',
            label: 'Days',
            align: 'center',
            width: 50,
        },
    ]

    const scaleUnit = scale.value
    if (scaleUnit === 'day') {
        gantt.config.scales = [
            { unit: 'week', step: 1, format: 'Week %W' },
            { unit: 'day',  step: 1, format: '%d %M' },
        ]
    } else if (scaleUnit === 'month') {
        gantt.config.scales = [
            { unit: 'year',  step: 1, format: '%Y' },
            { unit: 'month', step: 1, format: '%M' },
        ]
    } else if (scaleUnit === 'quarter') {
        gantt.config.scales = [
            { unit: 'year',    step: 1, format: '%Y' },
            { unit: 'quarter', step: 1, format: 'Q%q' },
        ]
    } else {
        gantt.config.scales = [
            { unit: 'month', step: 1, format: '%F %Y' },
            { unit: 'week',  step: 1, format: 'W%W' },
        ]
    }

    gantt.config.show_progress       = showProgress.value
    gantt.config.readonly            = isReadonly.value
    gantt.config.drag_move           = !isReadonly.value
    gantt.config.drag_resize         = !isReadonly.value
    gantt.config.drag_progress       = !isReadonly.value && showProgress.value
    gantt.config.open_tree_initially = true

    gantt.config.row_height   = 34
    gantt.config.bar_height   = 20
    gantt.config.grid_width   = 340
    gantt.config.date_format  = '%Y-%m-%d %H:%i'
}

function loadData() {
    const dataUrl = props.component.dataUrl as string | undefined
    if (!dataUrl) {
        gantt.clearAll()
        return
    }

    http.get(dataUrl, {
        onSuccess: (data) => {
            const raw = data?.data ?? data?.tasks ?? []
            const tasks = raw.map((t, i) => ({
                id:         (t.id as number | string | undefined) ?? i + 1,
                text:       String(t[textField.value]     ?? t.text ?? 'Task'),
                start_date: t[startField.value]           ?? t.start_date,
                end_date:   t[endField.value]             ?? t.end_date,
                duration:   t[durationField.value]        ?? t.duration ?? 1,
                progress:   t[progressField.value]        ?? t.progress ?? 0,
                parent:     (t.parent_id as number | string | undefined) ?? 0,
                open:       true,
            }))

            const links = (data?.links ?? []) as unknown[]

            gantt.clearAll()
            // dhtmlx-gantt's typing for parse() is strict; cast to pass through.
            gantt.parse({ data: tasks, links } as any)
        },
    })
}

onMounted(() => {
    if (!containerEl.value || initialized) return
    initialized = true

    configureGantt()
    gantt.init(containerEl.value)
    loadData()
})

onBeforeUnmount(() => {
    // dhtmlx-gantt doesn't expose a clean destroy — wipe data on unmount.
    gantt.clearAll()
})
</script>

<template>
    <FieldWrapper
        :component="component"
        :record="record"
        :errors="errors"
        class="col-span-full"
    >
        <template #default>

            <!-- Loading -->
            <div
                v-if="http.processing"
                class="flex items-center justify-center border border-paper-200 rounded-md bg-paper-50"
                :style="{ height: `${height}px` }"
            >
                <Loader2 class="w-5 h-5 animate-spin text-ink-400" />
            </div>

            <!-- Gantt container -->
            <div
                v-show="!http.processing"
                ref="containerEl"
                class="gantt_container w-full"
                :style="{ height: `${height}px` }"
            />

        </template>
    </FieldWrapper>
</template>
