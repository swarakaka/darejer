<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import { BellOff, CheckCheck, CircleCheck, CircleX, Info, Trash2, TriangleAlert } from 'lucide-vue-next'
import { computed, watch } from 'vue'
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/components/ui/sheet'
import { useAlerts, type AlertLevel, type AlertRecord } from '@/composables/useAlerts'
import useTranslation from '@/composables/useTranslation'

const props = defineProps<{ open: boolean }>()
const emit = defineEmits<{ (e: 'update:open', value: boolean): void }>()

const { __ } = useTranslation()
const { items, unreadCount, loading, loaded, loadList, markRead, markAllRead, destroy, clearAll } = useAlerts()

const isOpen = computed({
  get: () => props.open,
  set: (v) => emit('update:open', v),
})

// Lazy-load the list the first time the slideover opens. Refresh on every
// subsequent open so the user sees the freshest state even if Echo missed
// a beat (e.g. they were offline).
watch(isOpen, (open) => {
  if (open) void loadList()
})

const levelIcon: Record<AlertLevel, unknown> = {
  success: CircleCheck,
  error: CircleX,
  warning: TriangleAlert,
  info: Info,
}

const levelClass: Record<AlertLevel, string> = {
  success: 'text-success-600',
  error: 'text-danger-600',
  warning: 'text-warning-600',
  info: 'text-brand-600',
}

const levelBg: Record<AlertLevel, string> = {
  success: 'bg-success-50',
  error: 'bg-danger-50',
  warning: 'bg-warning-50',
  info: 'bg-brand-50',
}

function timeAgo(iso: string | null): string {
  if (!iso) return ''
  const then = new Date(iso).getTime()
  const diff = Math.max(0, (Date.now() - then) / 1000)
  if (diff < 60) return __('just now')
  if (diff < 3600) return __(':n min ago', { n: Math.floor(diff / 60) })
  if (diff < 86400) return __(':n h ago', { n: Math.floor(diff / 3600) })
  if (diff < 86400 * 7) return __(':n d ago', { n: Math.floor(diff / 86400) })
  return new Date(iso).toLocaleDateString()
}

function onClick(alert: AlertRecord): void {
  if (!alert.read_at) void markRead(alert.id)
  if (alert.link) {
    isOpen.value = false
    // Inertia visit — keeps the SPA shell, no full reload.
    router.visit(alert.link)
  }
}
</script>

<template>
  <Sheet v-model:open="isOpen">
    <SheetContent side="right" class="flex w-full flex-col gap-0 p-0 sm:max-w-md">
      <SheetHeader class="border-paper-200 bg-paper-50 border-b px-5 py-4">
        <SheetTitle class="text-ink-900 flex items-center gap-2 text-base font-semibold">
          {{ __('Notifications') }}
          <span
            v-if="unreadCount > 0"
            class="bg-brand-600 inline-flex h-5 min-w-5 items-center justify-center rounded-full px-1.5 text-[10px] font-semibold text-white tabular-nums"
          >
            {{ unreadCount }}
          </span>
        </SheetTitle>
        <SheetDescription class="text-ink-500 text-xs">
          {{ __('Recent alerts and updates for your account.') }}
        </SheetDescription>

        <div class="mt-2 flex items-center gap-2">
          <button
            type="button"
            class="text-ink-600 hover:bg-paper-100 hover:text-ink-900 inline-flex h-7 items-center gap-1.5 rounded-md px-2 text-xs font-medium transition-colors disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="unreadCount === 0"
            @click="markAllRead"
          >
            <CheckCheck class="h-3.5 w-3.5" />
            {{ __('Mark all read') }}
          </button>
          <span class="bg-paper-300 h-4 w-px" />
          <button
            type="button"
            class="text-ink-600 hover:bg-danger-50 hover:text-danger-600 inline-flex h-7 items-center gap-1.5 rounded-md px-2 text-xs font-medium transition-colors disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="items.length === 0"
            @click="clearAll"
          >
            <Trash2 class="h-3.5 w-3.5" />
            {{ __('Clear all') }}
          </button>
        </div>
      </SheetHeader>

      <div class="bg-paper-50 flex-1 overflow-y-auto">
        <!-- Skeleton -->
        <div v-if="loading && !loaded" class="space-y-3 p-4">
          <div v-for="i in 4" :key="i" class="border-paper-200 bg-card h-16 animate-pulse rounded-md border" />
        </div>

        <!-- Empty -->
        <div
          v-else-if="loaded && items.length === 0"
          class="flex flex-col items-center justify-center px-6 py-16 text-center"
        >
          <div
            class="border-paper-200 bg-card mb-3 flex h-14 w-14 items-center justify-center rounded-full border shadow-xs"
          >
            <BellOff class="text-ink-400 h-5 w-5" />
          </div>
          <p class="text-ink-700 text-sm font-semibold">{{ __("You're all caught up") }}</p>
          <p class="text-ink-400 mt-1 text-xs">{{ __('New notifications will appear here.') }}</p>
        </div>

        <!-- List -->
        <ul v-else class="divide-paper-200 divide-y">
          <li
            v-for="alert in items"
            :key="alert.id"
            class="group bg-card hover:bg-paper-100/70 relative cursor-pointer px-5 py-3 transition-colors"
            :class="alert.read_at ? '' : `bg-brand-50/60 hover:bg-brand-50`"
            @click="onClick(alert)"
          >
            <div class="flex items-start gap-3">
              <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md" :class="levelBg[alert.level]">
                <component :is="levelIcon[alert.level]" class="h-3.5 w-3.5" :class="levelClass[alert.level]" />
              </span>
              <div class="min-w-0 flex-1">
                <p class="text-ink-800 text-sm leading-snug" :class="alert.read_at ? 'font-normal' : `font-semibold`">
                  {{ alert.message }}
                </p>
                <div class="text-2xs text-ink-400 mt-1 tabular-nums">
                  {{ timeAgo(alert.created_at) }}
                </div>
              </div>
              <button
                type="button"
                class="text-ink-400 hover:bg-danger-50 hover:text-danger-600 rounded-md p-1 transition-colors"
                :aria-label="__('Delete notification')"
                @click.stop="destroy(alert.id)"
              >
                <Trash2 class="h-3.5 w-3.5" />
              </button>
            </div>
            <span
              v-if="!alert.read_at"
              class="bg-brand-600 absolute start-2 top-1/2 h-1.5 w-1.5 -translate-y-1/2 rounded-full"
            />
          </li>
        </ul>
      </div>
    </SheetContent>
  </Sheet>
</template>
