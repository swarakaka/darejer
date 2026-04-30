<script setup lang="ts">
import { ref, computed, watch, nextTick, onMounted, onBeforeUnmount } from 'vue'
import { router } from '@inertiajs/vue3'
import { onClickOutside } from '@vueuse/core'
import { Search, Command, CornerDownLeft, Loader2 } from 'lucide-vue-next'
import useTranslation from '@/composables/useTranslation'
import { useGlobalSearch, type SearchItem } from '@/composables/useGlobalSearch'

const { __ } = useTranslation()

const wrapper = ref<HTMLElement | null>(null)
const inputEl = ref<HTMLInputElement | null>(null)
const term    = ref('')
const open    = ref(false)
const activeIndex = ref(-1)

const { groups, total, loading, hasResults, search, reset } = useGlobalSearch()

// Flatten groups into a sequential list so arrow-key navigation works
// across group boundaries.
const flatItems = computed<Array<{ group: string; item: SearchItem }>>(() =>
    groups.value.flatMap(g => g.items.map(item => ({ group: g.type, item }))),
)

watch(term, (q) => {
    open.value = true
    activeIndex.value = -1
    search(q)
})

watch(open, (v) => {
    if (!v) activeIndex.value = -1
})

onClickOutside(wrapper, () => { open.value = false })

function focusInput(): void {
    inputEl.value?.focus()
}

function clear(): void {
    term.value = ''
    reset()
    open.value = false
}

function go(item: SearchItem): void {
    if (!item.url) return
    open.value = false
    clear()
    router.visit(item.url)
}

function onKeyDown(e: KeyboardEvent): void {
    if (!open.value) return

    if (e.key === 'Escape') {
        e.preventDefault()
        open.value = false
        return
    }

    const items = flatItems.value
    if (items.length === 0) return

    if (e.key === 'ArrowDown') {
        e.preventDefault()
        activeIndex.value = (activeIndex.value + 1) % items.length
    } else if (e.key === 'ArrowUp') {
        e.preventDefault()
        activeIndex.value = activeIndex.value <= 0
            ? items.length - 1
            : activeIndex.value - 1
    } else if (e.key === 'Enter') {
        const target = items[activeIndex.value >= 0 ? activeIndex.value : 0]
        if (target) {
            e.preventDefault()
            go(target.item)
        }
    }
}

// Cmd/Ctrl + K opens & focuses the search from anywhere on the page —
// matches the keyboard hint shown in the topbar input.
function onGlobalKey(e: KeyboardEvent): void {
    if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault()
        nextTick(() => focusInput())
    }
}

onMounted(() => window.addEventListener('keydown', onGlobalKey))
onBeforeUnmount(() => window.removeEventListener('keydown', onGlobalKey))

function flatIndex(groupIdx: number, itemIdx: number): number {
    let n = 0
    for (let i = 0; i < groupIdx; i++) n += groups.value[i].items.length
    return n + itemIdx
}
</script>

<template>
    <div ref="wrapper" class="relative w-full max-w-2xl min-w-0">
        <div class="relative group">
            <Search
                class="absolute inset-s-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5
                       text-ink-600 group-focus-within:text-brand-600 transition-colors pointer-events-none"
            />
            <input
                ref="inputEl"
                v-model="term"
                type="search"
                autocomplete="off"
                spellcheck="false"
                :placeholder="__('Search')"
                class="w-full h-7 ps-8 pe-16 text-[13px] bg-white/95 border border-transparent rounded-[2px] text-ink-900
                       placeholder:text-ink-500 focus:outline-none focus:bg-white focus:border-brand-300 focus:ring-0
                       focus:shadow-[inset_0_0_0_1px_var(--color-brand-300)] transition-colors duration-100"
                @focus="open = true"
                @keydown="onKeyDown"
            />
            <kbd
                v-if="!term"
                class="absolute inset-e-2 top-1/2 -translate-y-1/2 hidden md:flex items-center gap-1
                       text-2xs font-medium text-ink-600 bg-paper-100
                       border border-paper-200 rounded-[2px] px-1.5 h-4 tabular-nums pointer-events-none"
            >
                <Command class="w-2.5 h-2.5" />K
            </kbd>
            <Loader2
                v-else-if="loading"
                class="absolute inset-e-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-ink-500 animate-spin"
            />
        </div>

        <!-- Results dropdown -->
        <div
            v-if="open && term"
            class="absolute start-0 end-0 mt-1 z-50 max-h-[28rem] overflow-y-auto
                   rounded-none border border-paper-200 bg-white shadow-[0_6.4px_14.4px_rgba(0,0,0,0.13),_0_1.2px_3.6px_rgba(0,0,0,0.10)]"
        >
            <div
                v-if="loading && !hasResults"
                class="flex items-center gap-2 px-4 py-3 text-xs text-ink-400"
            >
                <Loader2 class="w-3.5 h-3.5 animate-spin" />
                {{ __('Searching…') }}
            </div>

            <div
                v-else-if="!hasResults"
                class="px-4 py-8 text-center"
            >
                <p class="text-sm text-ink-700 font-medium">{{ __('No matches for ":q"', { q: term }) }}</p>
                <p class="text-xs text-ink-400 mt-1">{{ __('Try a different name, code, or email.') }}</p>
            </div>

            <template v-else>
                <div
                    v-for="(group, gIdx) in groups"
                    :key="group.slug"
                    class="border-b border-paper-100 last:border-b-0"
                >
                    <div
                        class="px-4 pt-2.5 pb-1 text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-400 bg-paper-50/60"
                    >
                        {{ group.type }}
                    </div>

                    <button
                        v-for="(item, iIdx) in group.items"
                        :key="`${group.slug}-${item.id}`"
                        type="button"
                        class="w-full flex items-center justify-between gap-3 px-4 py-2 text-start
                               transition-colors"
                        :class="[
                            activeIndex === flatIndex(gIdx, iIdx)
                                ? 'bg-brand-50 text-brand-800'
                                : 'hover:bg-paper-50 text-ink-700',
                            !item.url ? 'opacity-60 cursor-default' : 'cursor-pointer',
                        ]"
                        :disabled="!item.url"
                        @mouseenter="activeIndex = flatIndex(gIdx, iIdx)"
                        @click="go(item)"
                    >
                        <div class="flex flex-col items-start min-w-0">
                            <span class="text-sm truncate font-medium">{{ item.label }}</span>
                            <span
                                v-if="item.subtitle"
                                class="text-xs text-ink-400 truncate tabular-nums"
                            >
                                {{ item.subtitle }}
                            </span>
                        </div>
                        <CornerDownLeft
                            v-if="activeIndex === flatIndex(gIdx, iIdx) && item.url"
                            class="w-3.5 h-3.5 text-ink-400 shrink-0 rtl:rotate-180"
                        />
                    </button>
                </div>

                <div class="px-4 py-2 border-t border-paper-100 text-2xs text-ink-400 flex items-center gap-3 bg-paper-50/60">
                    <span class="flex items-center gap-1"><kbd class="px-1.5 py-0.5 rounded-sm border border-paper-200 bg-white shadow-xs">↑↓</kbd> {{ __('navigate') }}</span>
                    <span class="flex items-center gap-1"><kbd class="px-1.5 py-0.5 rounded-sm border border-paper-200 bg-white shadow-xs">↵</kbd> {{ __('open') }}</span>
                    <span class="flex items-center gap-1"><kbd class="px-1.5 py-0.5 rounded-sm border border-paper-200 bg-white shadow-xs">esc</kbd> {{ __('close') }}</span>
                    <span class="ms-auto tabular-nums font-medium">{{ __(':n results', { n: total }) }}</span>
                </div>
            </template>
        </div>
    </div>
</template>
