<script setup lang="ts">
import { ref, computed, onMounted }  from 'vue'
import { router, useHttp }           from '@inertiajs/vue3'
import { useDataUrl }                from '@/composables/useDataUrl'
import { VueDraggable }              from 'vue-draggable-plus'
import { Loader2, GripVertical }     from 'lucide-vue-next'
import FieldWrapper                  from '@/components/darejer/FieldWrapper.vue'
import useTranslation                from '@/composables/useTranslation'
import type { DarejerComponent }     from '@/types/darejer'

const { __ } = useTranslation()

const props = defineProps<{
    component: DarejerComponent
    record:    Record<string, unknown>
    errors:    Record<string, string>
    formData?: Record<string, unknown>
}>()

interface KanbanCol {
    value:   string
    label:   string
    color?:  string
    limit?:  number
    locked?: boolean
}

interface CardTemplate {
    titleField:     string
    subtitleField?: string
    badgeField?:    string
    metaFields?:    { field: string; label: string }[]
    avatarField?:   string
    dateField?:     string
}

type CardRecord = Record<string, unknown>

const allCards = ref<CardRecord[]>([])
const updating = ref<Set<unknown>>(new Set())

// Shared data-loading composable; dedicated useHttp for PATCHing moves.
const { load, http: httpFetch } = useDataUrl<CardRecord>(
    props.component.dataUrl as string | undefined,
    { perPage: 500 },
)
const httpUpdate = useHttp<{ field: string; value: string | number | boolean | null }>({ field: '', value: null })

const columns     = computed((): KanbanCol[]   => (props.component.kanbanColumns as KanbanCol[])   ?? [])
const cardTpl     = computed((): CardTemplate  => (props.component.cardTemplate as CardTemplate)   ?? { titleField: 'name' })
const statusField = computed(() => (props.component.statusField as string) ?? 'status')
const editUrl     = computed(() => props.component.editUrl as string | undefined)
const editDialog  = computed(() => !!props.component.editDialog)
const updateUrl   = computed(() => props.component.updateUrl as string | undefined)
const isDisabled  = computed(() => !!props.component.disabled)

const columnCards = ref<Record<string, CardRecord[]>>({})

async function fetchAll() {
    const result = await load({ perPage: 500 })
    if (!result) return

    allCards.value = result.data

    const grouped: Record<string, CardRecord[]> = {}
    for (const col of columns.value) {
        grouped[col.value] = []
    }
    for (const card of allCards.value) {
        const colValue = String(card[statusField.value] ?? '')
        if (grouped[colValue] !== undefined) {
            grouped[colValue].push(card)
        }
    }
    columnCards.value = grouped
}

onMounted(() => fetchAll())

function onCardMoved(card: CardRecord, fromColumn: string, toColumn: string) {
    if (fromColumn === toColumn) return
    if (isDisabled.value) return

    const id = (card.id as unknown) ?? card
    updating.value.add(id)
    updating.value = new Set(updating.value)

    // Optimistic local update.
    card[statusField.value] = toColumn

    if (!updateUrl.value) {
        updating.value.delete(id)
        updating.value = new Set(updating.value)
        return
    }

    const url = updateUrl.value.replace(/\{(\w+)\}/g, (_, key) => String(card[key] ?? ''))

    // useHttp sends the instance's data fields as the request body.
    httpUpdate.field = statusField.value
    httpUpdate.value = toColumn

    httpUpdate.patch(url, {
        onError: () => {
            // Revert on failure.
            card[statusField.value] = fromColumn
            if (!columnCards.value[fromColumn].some(c => (c.id as unknown) === id)) {
                columnCards.value[fromColumn].push(card)
            }
            columnCards.value[toColumn] = columnCards.value[toColumn].filter(c => (c.id as unknown) !== id)
        },
        onFinish: () => {
            updating.value.delete(id)
            updating.value = new Set(updating.value)
        },
    })
}

function onCardClick(card: CardRecord) {
    if (!editUrl.value) return
    const url = editUrl.value.replace(/\{(\w+)\}/g, (_, key) => String(card[key] ?? ''))

    if (editDialog.value) {
        router.visit(url, { data: { _dialog: '1' } })
    } else {
        router.visit(url)
    }
}

// Ledger-tuned column colors. `blue|purple` map to brand; semantic names map
// directly; others fall back to a neutral paper/ink treatment.
function colHeaderClass(color?: string): string {
    const map: Record<string, string> = {
        brand:   'bg-brand-50 border-brand-100 text-brand-700',
        blue:    'bg-brand-50 border-brand-100 text-brand-700',
        purple:  'bg-brand-50 border-brand-100 text-brand-700',
        success: 'bg-success-50 border-success-100 text-success-700',
        green:   'bg-success-50 border-success-100 text-success-700',
        warning: 'bg-warning-50 border-warning-100 text-warning-700',
        yellow:  'bg-warning-50 border-warning-100 text-warning-700',
        danger:  'bg-danger-50 border-danger-100 text-danger-700',
        red:     'bg-danger-50 border-danger-100 text-danger-700',
        neutral: 'bg-paper-75 border-paper-200 text-ink-600',
        gray:    'bg-paper-75 border-paper-200 text-ink-600',
    }
    return map[color ?? 'gray'] ?? map.gray
}

function colBadgeClass(color?: string): string {
    const map: Record<string, string> = {
        brand:   'bg-brand-100 text-brand-700',
        blue:    'bg-brand-100 text-brand-700',
        purple:  'bg-brand-100 text-brand-700',
        success: 'bg-success-100 text-success-700',
        green:   'bg-success-100 text-success-700',
        warning: 'bg-warning-100 text-warning-700',
        yellow:  'bg-warning-100 text-warning-700',
        danger:  'bg-danger-100 text-danger-700',
        red:     'bg-danger-100 text-danger-700',
        neutral: 'bg-paper-200 text-ink-600',
        gray:    'bg-paper-200 text-ink-600',
    }
    return map[color ?? 'gray'] ?? map.gray
}

function cardTitle(card: CardRecord): string {
    return String(card[cardTpl.value.titleField] ?? '—')
}

function cardSubtitle(card: CardRecord): string | null {
    if (!cardTpl.value.subtitleField) return null
    const v = card[cardTpl.value.subtitleField]
    return v === undefined || v === null ? '' : String(v)
}

function cardBadge(card: CardRecord): string | null {
    if (!cardTpl.value.badgeField) return null
    const v = card[cardTpl.value.badgeField]
    return v === undefined || v === null ? '' : String(v)
}

function cardDate(card: CardRecord): string | null {
    if (!cardTpl.value.dateField) return null
    const val = card[cardTpl.value.dateField]
    if (!val) return null
    return new Date(String(val)).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
}

const ISO_DATE_RE = /^\d{4}-\d{2}-\d{2}(?:[T ]\d{2}:\d{2}(?::\d{2})?(?:\.\d+)?(?:Z|[+-]\d{2}:?\d{2})?)?$/

function formatMetaValue(value: unknown): string {
    if (value === null || value === undefined || value === '') return '—'
    if (typeof value === 'string' && ISO_DATE_RE.test(value)) {
        const d = new Date(value)
        if (!isNaN(d.getTime())) {
            return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
        }
    }
    return String(value)
}

function avatarInitials(card: CardRecord): string {
    if (!cardTpl.value.avatarField) return ''
    const name = String(card[cardTpl.value.avatarField] ?? '')
    return name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase()
}

function isOverLimit(col: KanbanCol): boolean {
    if (!col.limit) return false
    return (columnCards.value[col.value]?.length ?? 0) > col.limit
}

function onDragEnd(col: KanbanCol) {
    // After drop, any card whose status doesn't match this column was moved
    // here from another column — fire the PATCH for each.
    for (const card of columnCards.value[col.value] ?? []) {
        if (card[statusField.value] !== col.value) {
            const fromCol = String(card[statusField.value])
            onCardMoved(card, fromCol, col.value)
        }
    }
}
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

            <!-- Loading -->
            <div v-if="httpFetch.processing" class="flex items-center justify-center h-40 text-ink-400">
                <Loader2 class="w-5 h-5 animate-spin" />
            </div>

            <!-- Board -->
            <div
                v-else
                class="flex gap-3 overflow-x-auto px-4 pb-2 h-full"
                style="min-height: 24rem;"
            >
                <div
                    v-for="col in columns"
                    :key="col.value"
                    class="flex flex-col flex-1 min-w-[16rem] rounded-md border overflow-hidden bg-white"
                    :class="isOverLimit(col) ? 'border-danger-300' : 'border-paper-200'"
                >
                    <!-- Column header -->
                    <div
                        class="flex items-center justify-between px-3 py-2 border-b"
                        :class="colHeaderClass(col.color)"
                    >
                        <span class="text-xs font-semibold tracking-wide uppercase">{{ col.label }}</span>
                        <div class="flex items-center gap-1.5">
                            <span
                                v-if="col.limit"
                                class="text-[10px] font-medium tabular-nums"
                                :class="isOverLimit(col) ? 'text-danger-600' : 'text-ink-400'"
                            >
                                {{ columnCards[col.value]?.length ?? 0 }}/{{ col.limit }}
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center justify-center h-4 min-w-[1rem] px-1 rounded-full text-[10px] font-semibold tabular-nums"
                                :class="colBadgeClass(col.color)"
                            >
                                {{ columnCards[col.value]?.length ?? 0 }}
                            </span>
                        </div>
                    </div>

                    <!-- Cards drop zone -->
                    <VueDraggable
                        v-model="columnCards[col.value]"
                        group="kanban"
                        :disabled="isDisabled || col.locked"
                        class="flex flex-col gap-2 p-2 flex-1 min-h-[8rem] bg-paper-50/60"
                        ghost-class="opacity-40"
                        chosen-class="rotate-1 scale-[1.02]"
                        drag-class="opacity-90"
                        @end="onDragEnd(col)"
                    >
                        <div
                            v-for="card in (columnCards[col.value] ?? [])"
                            :key="String(card.id ?? card)"
                            class="relative bg-white border border-paper-200 rounded-md p-2.5 transition-all duration-100 group"
                            :class="[
                                isDisabled || col.locked
                                    ? 'cursor-default'
                                    : 'cursor-grab active:cursor-grabbing hover:border-paper-300',
                                updating.has(card.id ?? card) ? 'opacity-50' : '',
                            ]"
                            @click="onCardClick(card)"
                        >
                            <!-- Title + drag handle -->
                            <div class="flex items-start justify-between gap-2 mb-1">
                                <p
                                    class="text-sm font-medium text-ink-800 leading-tight line-clamp-2"
                                    :class="editUrl ? 'group-hover:text-brand-600 cursor-pointer' : ''"
                                >
                                    {{ cardTitle(card) }}
                                </p>
                                <GripVertical
                                    v-if="!isDisabled && !col.locked"
                                    class="w-3.5 h-3.5 text-ink-300 shrink-0 mt-0.5 opacity-0 group-hover:opacity-100 transition-opacity"
                                />
                            </div>

                            <!-- Subtitle -->
                            <p
                                v-if="cardSubtitle(card)"
                                class="text-xs text-ink-500 mb-1.5 line-clamp-1 tabular-nums"
                            >
                                {{ cardSubtitle(card) }}
                            </p>

                            <!-- Meta -->
                            <div
                                v-if="cardTpl.metaFields?.length"
                                class="flex flex-col gap-0.5 mb-1.5"
                            >
                                <div
                                    v-for="meta in cardTpl.metaFields"
                                    :key="meta.field"
                                    class="flex items-center gap-1 text-xs text-ink-500"
                                >
                                    <span class="text-ink-400 shrink-0">{{ meta.label }}:</span>
                                    <span class="truncate">{{ formatMetaValue(card[meta.field]) }}</span>
                                </div>
                            </div>

                            <!-- Footer -->
                            <div class="flex items-center justify-between gap-1 mt-1.5">
                                <div class="flex items-center gap-1">
                                    <span
                                        v-if="cardBadge(card)"
                                        class="inline-flex items-center px-1.5 py-0.5 rounded-sm
                                               text-[10px] font-semibold uppercase tracking-wide
                                               bg-paper-100 text-ink-600 border border-paper-200"
                                    >
                                        {{ cardBadge(card) }}
                                    </span>

                                    <span
                                        v-if="cardDate(card)"
                                        class="text-[10px] text-ink-400 tabular-nums"
                                    >
                                        {{ cardDate(card) }}
                                    </span>
                                </div>

                                <div
                                    v-if="cardTpl.avatarField && card[cardTpl.avatarField]"
                                    class="w-5 h-5 rounded-full bg-brand-100 flex items-center justify-center text-[9px] font-bold text-brand-700 shrink-0"
                                >
                                    {{ avatarInitials(card) }}
                                </div>
                            </div>

                            <!-- Updating spinner overlay -->
                            <div
                                v-if="updating.has(card.id ?? card)"
                                class="absolute inset-0 flex items-center justify-center bg-white/60 rounded-md"
                            >
                                <Loader2 class="w-4 h-4 animate-spin text-brand-600" />
                            </div>
                        </div>

                        <!-- Empty column placeholder -->
                        <div
                            v-if="!columnCards[col.value]?.length"
                            class="flex items-center justify-center h-16 rounded-md border border-dashed border-paper-300 text-xs text-ink-300"
                        >
                            {{ __('Drop cards here') }}
                        </div>
                    </VueDraggable>
                </div>
            </div>

        </template>
    </FieldWrapper>
</template>
