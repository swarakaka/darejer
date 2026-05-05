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
const term = ref('')
const open = ref(false)
const activeIndex = ref(-1)

const { groups, total, loading, hasResults, search, reset } = useGlobalSearch()

// Flatten groups into a sequential list so arrow-key navigation works
// across group boundaries.
const flatItems = computed<Array<{ group: string; item: SearchItem }>>(() =>
  groups.value.flatMap((g) => g.items.map((item) => ({ group: g.type, item }))),
)

watch(term, (q) => {
  open.value = true
  activeIndex.value = -1
  search(q)
})

watch(open, (v) => {
  if (!v) activeIndex.value = -1
})

onClickOutside(wrapper, () => {
  open.value = false
})

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
    activeIndex.value = activeIndex.value <= 0 ? items.length - 1 : activeIndex.value - 1
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
    <div class="group relative">
      <Search
        class="pointer-events-none absolute inset-s-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-ink-600 transition-colors group-focus-within:text-brand-600"
      />
      <input
        ref="inputEl"
        v-model="term"
        type="search"
        autocomplete="off"
        spellcheck="false"
        :placeholder="__('Search')"
        class="h-7 w-full rounded-[2px] border border-transparent bg-white/95 ps-8 pe-16 text-[13px] text-ink-900 transition-colors duration-100 placeholder:text-ink-500 focus:border-brand-300 focus:bg-white focus:shadow-[inset_0_0_0_1px_var(--color-brand-300)] focus:ring-0 focus:outline-none"
        @focus="open = true"
        @keydown="onKeyDown"
      />
      <kbd
        v-if="!term"
        class="pointer-events-none absolute inset-e-2 top-1/2 hidden h-4 -translate-y-1/2 items-center gap-1 rounded-[2px] border border-paper-200 bg-paper-100 px-1.5 text-2xs font-medium text-ink-600 tabular-nums md:flex"
      >
        <Command class="h-2.5 w-2.5" />K
      </kbd>
      <Loader2
        v-else-if="loading"
        class="absolute inset-e-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 animate-spin text-ink-500"
      />
    </div>

    <!-- Results dropdown -->
    <div
      v-if="open && term"
      class="absolute start-0 end-0 z-50 mt-1 max-h-[28rem] overflow-y-auto rounded-none border border-(--border) bg-popover text-popover-foreground shadow-[0_6.4px_14.4px_rgba(0,0,0,0.13),_0_1.2px_3.6px_rgba(0,0,0,0.10)]"
    >
      <div
        v-if="loading && !hasResults"
        class="flex items-center gap-2 px-4 py-3 text-xs text-ink-400"
      >
        <Loader2 class="h-3.5 w-3.5 animate-spin" />
        {{ __('Searching…') }}
      </div>

      <div v-else-if="!hasResults" class="px-4 py-8 text-center">
        <p class="text-sm font-medium text-ink-700">{{ __('No matches for ":q"', { q: term }) }}</p>
        <p class="mt-1 text-xs text-ink-400">{{ __('Try a different name, code, or email.') }}</p>
      </div>

      <template v-else>
        <div
          v-for="(group, gIdx) in groups"
          :key="group.slug"
          class="border-b border-paper-100 last:border-b-0"
        >
          <div
            class="bg-paper-50/60 px-4 pt-2.5 pb-1 text-[10px] font-semibold tracking-[0.16em] text-ink-400 uppercase"
          >
            {{ group.type }}
          </div>

          <button
            v-for="(item, iIdx) in group.items"
            :key="`${group.slug}-${item.id}`"
            type="button"
            class="flex w-full items-center justify-between gap-3 px-4 py-2 text-start transition-colors"
            :class="[
              activeIndex === flatIndex(gIdx, iIdx)
                ? 'bg-brand-50 text-brand-800'
                : `text-ink-700 hover:bg-paper-50`,
              !item.url ? 'cursor-default opacity-60' : `cursor-pointer`,
            ]"
            :disabled="!item.url"
            @mouseenter="activeIndex = flatIndex(gIdx, iIdx)"
            @click="go(item)"
          >
            <div class="flex min-w-0 flex-col items-start">
              <span class="truncate text-sm font-medium">{{ item.label }}</span>
              <span v-if="item.subtitle" class="truncate text-xs text-ink-400 tabular-nums">
                {{ item.subtitle }}
              </span>
            </div>
            <CornerDownLeft
              v-if="activeIndex === flatIndex(gIdx, iIdx) && item.url"
              class="h-3.5 w-3.5 shrink-0 text-ink-400 rtl:rotate-180"
            />
          </button>
        </div>

        <div
          class="flex items-center gap-3 border-t border-paper-100 bg-paper-50/60 px-4 py-2 text-2xs text-ink-400"
        >
          <span class="flex items-center gap-1"
            ><kbd class="rounded-sm border border-paper-200 bg-paper-50 px-1.5 py-0.5 shadow-xs"
              >↑↓</kbd
            >
            {{ __('navigate') }}</span
          >
          <span class="flex items-center gap-1"
            ><kbd class="rounded-sm border border-paper-200 bg-paper-50 px-1.5 py-0.5 shadow-xs"
              >↵</kbd
            >
            {{ __('open') }}</span
          >
          <span class="flex items-center gap-1"
            ><kbd class="rounded-sm border border-paper-200 bg-paper-50 px-1.5 py-0.5 shadow-xs"
              >esc</kbd
            >
            {{ __('close') }}</span
          >
          <span class="ms-auto font-medium tabular-nums">{{ __(':n results', { n: total }) }}</span>
        </div>
      </template>
    </div>
  </div>
</template>
