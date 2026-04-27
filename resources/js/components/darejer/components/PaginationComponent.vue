<script setup lang="ts">
import { computed }        from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'
import type { DarejerComponent, PaginatedData } from '@/types/darejer'

const props = defineProps<{
    component: DarejerComponent
    record:    Record<string, unknown>
    errors:    Record<string, string>
    formData?: Record<string, unknown>
}>()

const page    = usePage()
const dataKey = computed(() => (props.component.dataKey as string) ?? 'data')

const paginated = computed((): PaginatedData | null => {
    const data = (page.props as Record<string, unknown>)[dataKey.value]
    return (data as PaginatedData) ?? null
})

const current = computed(() => paginated.value?.current_page ?? 1)
const last    = computed(() => paginated.value?.last_page    ?? 1)
const total   = computed(() => paginated.value?.total        ?? 0)
const from    = computed(() => paginated.value?.from         ?? 0)
const to      = computed(() => paginated.value?.to           ?? 0)

const pages = computed(() => {
    const t     = last.value
    const c     = current.value
    const delta = 2
    const range: (number | '...')[] = []

    for (let i = Math.max(1, c - delta); i <= Math.min(t, c + delta); i++) {
        range.push(i)
    }

    if ((range[0] as number) > 1) {
        if ((range[0] as number) > 2) range.unshift('...')
        range.unshift(1)
    }

    if ((range[range.length - 1] as number) < t) {
        if ((range[range.length - 1] as number) < t - 1) range.push('...')
        range.push(t)
    }

    return range
})

function goTo(p: number) {
    router.get(
        window.location.pathname,
        { ...Object.fromEntries(new URLSearchParams(window.location.search)), page: p },
        { preserveState: true, preserveScroll: true, replace: true }
    )
}
</script>

<template>
    <div
        v-if="paginated && total > (paginated.per_page ?? 15)"
        class="flex items-center justify-between px-1 py-2 col-span-full"
    >
        <span class="text-xs text-ink-400 tabular-nums">
            Showing {{ from }}–{{ to }} of {{ total }} records
        </span>

        <div class="flex items-center gap-1">
            <button
                type="button"
                :disabled="current <= 1"
                class="flex items-center justify-center w-7 h-7 rounded-sm border border-paper-300
                       text-ink-500 hover:bg-paper-100 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                @click="goTo(current - 1)"
            >
                <ChevronLeft class="w-3.5 h-3.5" />
            </button>

            <template v-for="(p, idx) in pages" :key="`${idx}-${p}`">
                <span
                    v-if="p === '...'"
                    class="flex items-center justify-center w-7 h-7 text-xs text-ink-300"
                >
                    …
                </span>
                <button
                    v-else
                    type="button"
                    class="flex items-center justify-center w-7 h-7 rounded-sm text-xs border tabular-nums transition-colors"
                    :class="current === p
                        ? 'bg-brand-600 text-white border-brand-600'
                        : 'border-paper-300 text-ink-600 hover:bg-paper-100'"
                    @click="goTo(p as number)"
                >
                    {{ p }}
                </button>
            </template>

            <button
                type="button"
                :disabled="current >= last"
                class="flex items-center justify-center w-7 h-7 rounded-sm border border-paper-300
                       text-ink-500 hover:bg-paper-100 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                @click="goTo(current + 1)"
            >
                <ChevronRight class="w-3.5 h-3.5" />
            </button>
        </div>
    </div>
</template>
