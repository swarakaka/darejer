<script setup lang="ts">
import { ref, computed }  from 'vue'
import { router }         from '@inertiajs/vue3'
import { X }              from 'lucide-vue-next'
import { Button }         from '@/components/ui/button'
import type { DarejerComponent } from '@/types/darejer'

const props = defineProps<{
    component: DarejerComponent
    record:    Record<string, unknown>
    errors:    Record<string, string>
    formData?: Record<string, unknown>
}>()

interface FilterDef {
    field:        string
    label:        string
    type:         string
    options?:     { value: string; label: string }[]
    placeholder?: string
    default?:     unknown
}

const filters = computed((): FilterDef[] => (props.component.filters as FilterDef[]) ?? [])
const layout  = computed(() => (props.component.layout as string) ?? 'bar')
const isBar   = computed(() => layout.value === 'bar')
const isOpen  = ref(!props.component.collapsed)

const values = ref<Record<string, string>>(
    Object.fromEntries(
        filters.value.map(f => [f.field, String(f.default ?? '')])
    )
)

const activeCount = computed(() =>
    Object.values(values.value).filter(v => v !== '' && v !== null).length
)

function apply() {
    router.get(
        window.location.pathname,
        { ...values.value },
        { preserveState: true, preserveScroll: true, replace: true }
    )
}

function reset() {
    values.value = Object.fromEntries(filters.value.map(f => [f.field, '']))
    apply()
}

let applyTimer: ReturnType<typeof setTimeout>
function onFilterChange() {
    clearTimeout(applyTimer)
    applyTimer = setTimeout(() => apply(), 400)
}
</script>

<template>
    <div class="col-span-full">

        <!-- Bar layout -->
        <div
            v-if="isBar"
            class="flex flex-wrap items-end gap-3 p-3 bg-paper-75 border border-paper-200 rounded-md mb-3"
        >
            <div
                v-for="filter in filters"
                :key="filter.field"
                class="flex flex-col gap-1 min-w-[10rem]"
            >
                <label class="text-xs font-medium text-ink-500">{{ filter.label }}</label>

                <input
                    v-if="filter.type === 'text'"
                    v-model="values[filter.field]"
                    type="text"
                    :placeholder="filter.placeholder ?? `Filter ${filter.label}…`"
                    class="h-8 px-2.5 text-sm border border-paper-300 rounded-sm bg-white
                           placeholder:text-ink-400 focus:outline-none focus:border-brand-500 transition-colors"
                    @input="onFilterChange"
                />

                <select
                    v-else-if="filter.type === 'select'"
                    v-model="values[filter.field]"
                    class="h-8 px-2 text-sm border border-paper-300 rounded-sm bg-white
                           focus:outline-none focus:border-brand-500 transition-colors"
                    @change="onFilterChange"
                >
                    <option value="">All</option>
                    <option v-for="opt in filter.options" :key="opt.value" :value="opt.value">
                        {{ opt.label }}
                    </option>
                </select>

                <input
                    v-else-if="filter.type === 'date'"
                    v-model="values[filter.field]"
                    type="date"
                    class="h-8 px-2.5 text-sm border border-paper-300 rounded-sm bg-white
                           focus:outline-none focus:border-brand-500 transition-colors"
                    @change="onFilterChange"
                />

                <select
                    v-else-if="filter.type === 'boolean'"
                    v-model="values[filter.field]"
                    class="h-8 px-2 text-sm border border-paper-300 rounded-sm bg-white
                           focus:outline-none focus:border-brand-500 transition-colors"
                    @change="onFilterChange"
                >
                    <option value="">All</option>
                    <option value="1">Yes</option>
                    <option value="0">No</option>
                </select>
            </div>

            <Button
                v-if="activeCount > 0"
                variant="ghost"
                class="h-8 text-sm text-ink-500 hover:text-ink-800 gap-1.5"
                @click="reset"
            >
                <X class="w-3.5 h-3.5" />
                Clear ({{ activeCount }})
            </Button>
        </div>

        <!-- Sidebar layout -->
        <div v-else class="flex gap-4">
            <div
                v-if="isOpen"
                class="w-52 shrink-0 flex flex-col gap-3 p-3 border border-paper-200 rounded-md bg-paper-75 self-start"
            >
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-ink-600 uppercase tracking-wide">Filters</span>
                    <button
                        v-if="activeCount > 0"
                        type="button"
                        class="text-xs text-brand-600 hover:text-brand-700"
                        @click="reset"
                    >
                        Clear all
                    </button>
                </div>

                <div
                    v-for="filter in filters"
                    :key="filter.field"
                    class="flex flex-col gap-1"
                >
                    <label class="text-xs font-medium text-ink-500">{{ filter.label }}</label>

                    <input
                        v-if="filter.type === 'text'"
                        v-model="values[filter.field]"
                        type="text"
                        :placeholder="filter.placeholder ?? ''"
                        class="h-8 px-2.5 text-sm border border-paper-300 rounded-sm bg-white
                               placeholder:text-ink-400 focus:outline-none focus:border-brand-500"
                        @input="onFilterChange"
                    />

                    <select
                        v-else-if="filter.type === 'select'"
                        v-model="values[filter.field]"
                        class="h-8 px-2 text-sm border border-paper-300 rounded-sm bg-white
                               focus:outline-none focus:border-brand-500"
                        @change="onFilterChange"
                    >
                        <option value="">All</option>
                        <option v-for="opt in filter.options" :key="opt.value" :value="opt.value">
                            {{ opt.label }}
                        </option>
                    </select>
                </div>
            </div>

            <div class="flex-1 min-w-0">
                <slot />
            </div>
        </div>

    </div>
</template>
