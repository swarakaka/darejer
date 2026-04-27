<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import FullCalendar                 from '@fullcalendar/vue3'
import dayGridPlugin                from '@fullcalendar/daygrid'
import timeGridPlugin               from '@fullcalendar/timegrid'
import listPlugin                   from '@fullcalendar/list'
import interactionPlugin            from '@fullcalendar/interaction'
import type { CalendarOptions, EventClickArg, DateSelectArg } from '@fullcalendar/core'
import { useHttp, router }          from '@inertiajs/vue3'
import { Loader2 }                  from 'lucide-vue-next'
import FieldWrapper                 from '@/components/darejer/FieldWrapper.vue'
import useTranslation               from '@/composables/useTranslation'
import type { DarejerComponent }    from '@/types/darejer'

const { __ } = useTranslation()

const props = defineProps<{
    component: DarejerComponent
    record:    Record<string, unknown>
    errors:    Record<string, string>
    formData?: Record<string, unknown>
}>()

const isReadonly   = computed(() => !!props.component.readonly)
const height       = computed(() => (props.component.height as number) ?? 500)
const initialView  = computed(() => (props.component.initialView as string) ?? 'dayGridMonth')
const titleField   = computed(() => (props.component.titleField as string) ?? 'title')
const startField   = computed(() => (props.component.startField as string) ?? 'start')
const endField     = computed(() => (props.component.endField   as string) ?? 'end')
const colorField   = computed(() => props.component.colorField as string | undefined)
const defaultColor = computed(() => (props.component.defaultColor as string) ?? '#0d5e4d')
const editUrl      = computed(() => props.component.editUrl    as string | undefined)
const createUrl    = computed(() => props.component.createUrl  as string | undefined)
const editDialog   = computed(() => !!props.component.editDialog)
const createDialog = computed(() => !!props.component.createDialog)

const availableViews = computed(() =>
    (props.component.views as string[]) ?? ['dayGridMonth', 'timeGridWeek', 'timeGridDay', 'listWeek']
)

const events = ref<Record<string, unknown>[]>([])

const http = useHttp<Record<string, never>, { data?: Record<string, unknown>[] }>()

function loadEvents() {
    const dataUrl = props.component.dataUrl as string | undefined
    if (!dataUrl) return

    http.get(dataUrl, {
        onSuccess: (data) => {
            events.value = (data?.data ?? []).map((row) => ({
                id:    String(row.id ?? ''),
                title: String(row[titleField.value] ?? __('Untitled')),
                start: String(row[startField.value] ?? ''),
                end:   row[endField.value] ? String(row[endField.value]) : undefined,
                color: colorField.value
                    ? String(row[colorField.value] ?? defaultColor.value)
                    : defaultColor.value,
                extendedProps: row,
            }))
        },
    })
}

onMounted(() => loadEvents())

function onEventClick(info: EventClickArg) {
    if (!editUrl.value) return
    const url = editUrl.value.replace(/\{(\w+)\}/g, (_, key) => {
        const raw = info.event.extendedProps?.[key]
        return String(raw ?? info.event.id ?? '')
    })
    if (editDialog.value) {
        router.visit(`${url}?_dialog=1`)
    } else {
        router.visit(url)
    }
}

function onDateSelect(info: DateSelectArg) {
    if (isReadonly.value || !createUrl.value) return
    const url = `${createUrl.value}?start=${info.startStr}&end=${info.endStr}`
    if (createDialog.value) {
        router.visit(`${url}&_dialog=1`)
    } else {
        router.visit(url)
    }
}

const headerToolbar = computed(() => {
    const viewButtons = availableViews.value.join(',')
    return {
        left:   'prev,next today',
        center: 'title',
        right:  viewButtons,
    }
})

const viewLabels: Record<string, string> = {
    dayGridMonth: __('Month'),
    timeGridWeek: __('Week'),
    timeGridDay:  __('Day'),
    listWeek:     __('List'),
}

const calendarOptions = computed((): CalendarOptions => ({
    plugins: [dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin],
    initialView:     initialView.value,
    headerToolbar:   headerToolbar.value,
    height:          height.value,
    events:          events.value as unknown as CalendarOptions['events'],
    editable:        !isReadonly.value,
    selectable:      !isReadonly.value && !!createUrl.value,
    selectMirror:    true,
    dayMaxEvents:    true,
    weekends:        true,
    eventClick:      onEventClick,
    select:          onDateSelect,
    views: {
        dayGridMonth: { buttonText: viewLabels.dayGridMonth },
        timeGridWeek: { buttonText: viewLabels.timeGridWeek },
        timeGridDay:  { buttonText: viewLabels.timeGridDay  },
        listWeek:     { buttonText: viewLabels.listWeek     },
    },
    eventTimeFormat: {
        hour:   '2-digit',
        minute: '2-digit',
        meridiem: false,
    },
    slotLabelFormat: {
        hour:   '2-digit',
        minute: '2-digit',
        meridiem: false,
    },
}))
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

            <!-- Calendar -->
            <div
                v-else
                class="border border-paper-200 rounded-md overflow-hidden bg-white"
            >
                <FullCalendar :options="calendarOptions" />
            </div>

        </template>
    </FieldWrapper>
</template>
