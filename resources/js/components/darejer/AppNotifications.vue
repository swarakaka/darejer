<script setup lang="ts">
import { computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet'
import {
    BellOff,
    CheckCheck,
    CircleCheck,
    CircleX,
    ExternalLink,
    Info,
    Trash2,
    TriangleAlert,
} from 'lucide-vue-next'
import useTranslation from '@/composables/useTranslation'
import { useAlerts, type AlertLevel, type AlertRecord } from '@/composables/useAlerts'

const props = defineProps<{ open: boolean }>()
const emit  = defineEmits<{ (e: 'update:open', value: boolean): void }>()

const { __ } = useTranslation()
const {
    items,
    unreadCount,
    loading,
    loaded,
    loadList,
    markRead,
    markAllRead,
    destroy,
    clearAll,
} = useAlerts()

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
    error:   CircleX,
    warning: TriangleAlert,
    info:    Info,
}

const levelClass: Record<AlertLevel, string> = {
    success: 'text-success-600',
    error:   'text-danger-600',
    warning: 'text-warning-600',
    info:    'text-info-600',
}

function timeAgo(iso: string | null): string {
    if (!iso) return ''
    const then = new Date(iso).getTime()
    const diff = Math.max(0, (Date.now() - then) / 1000)
    if (diff < 60)        return __('just now')
    if (diff < 3600)      return __(':n min ago', { n: Math.floor(diff / 60) })
    if (diff < 86400)     return __(':n h ago',   { n: Math.floor(diff / 3600) })
    if (diff < 86400 * 7) return __(':n d ago',   { n: Math.floor(diff / 86400) })
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
        <SheetContent
            side="right"
            class="w-full sm:max-w-md flex flex-col gap-0 p-0"
        >
            <SheetHeader class="px-5 py-4 border-b border-paper-200">
                <SheetTitle class="text-base font-semibold text-ink-800 flex items-center gap-2">
                    {{ __('Notifications') }}
                    <span
                        v-if="unreadCount > 0"
                        class="inline-flex items-center justify-center min-w-5 h-5 px-1.5 rounded-full
                               bg-brand-600 text-white text-[10px] font-semibold tabular-nums"
                    >
                        {{ unreadCount }}
                    </span>
                </SheetTitle>
                <SheetDescription class="text-xs text-ink-400">
                    {{ __('Recent alerts and updates for your account.') }}
                </SheetDescription>

                <div class="flex items-center gap-2 mt-2">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 text-xs text-ink-600 hover:text-ink-900
                               disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        :disabled="unreadCount === 0"
                        @click="markAllRead"
                    >
                        <CheckCheck class="w-3.5 h-3.5" />
                        {{ __('Mark all read') }}
                    </button>
                    <span class="h-3 w-px bg-paper-300" />
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 text-xs text-ink-600 hover:text-danger-600
                               disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        :disabled="items.length === 0"
                        @click="clearAll"
                    >
                        <Trash2 class="w-3.5 h-3.5" />
                        {{ __('Clear all') }}
                    </button>
                </div>
            </SheetHeader>

            <div class="flex-1 overflow-y-auto">
                <!-- Skeleton -->
                <div v-if="loading && !loaded" class="p-4 space-y-3">
                    <div
                        v-for="i in 4"
                        :key="i"
                        class="h-14 rounded-sm bg-paper-100 animate-pulse"
                    />
                </div>

                <!-- Empty -->
                <div
                    v-else-if="loaded && items.length === 0"
                    class="flex flex-col items-center justify-center py-16 px-6 text-center"
                >
                    <div class="w-12 h-12 rounded-full bg-paper-100 flex items-center justify-center mb-3">
                        <BellOff class="w-5 h-5 text-ink-400" />
                    </div>
                    <p class="text-sm font-medium text-ink-700">{{ __('You\'re all caught up') }}</p>
                    <p class="text-xs text-ink-400 mt-1">{{ __('New notifications will appear here.') }}</p>
                </div>

                <!-- List -->
                <ul v-else class="divide-y divide-paper-200">
                    <li
                        v-for="alert in items"
                        :key="alert.id"
                        class="group relative px-5 py-3 transition-colors hover:bg-paper-50 cursor-pointer"
                        :class="alert.read_at ? '' : 'bg-brand-50/60'"
                        @click="onClick(alert)"
                    >
                        <div class="flex items-start gap-3">
                            <component
                                :is="levelIcon[alert.level]"
                                class="w-4 h-4 mt-0.5 shrink-0"
                                :class="levelClass[alert.level]"
                            />
                            <div class="flex-1 min-w-0">
                                <p
                                    class="text-sm leading-snug text-ink-800"
                                    :class="alert.read_at ? 'font-normal' : 'font-medium'"
                                >
                                    {{ alert.message }}
                                </p>
                                <div class="flex items-center gap-2 mt-1 text-2xs text-ink-400 tabular-nums">
                                    <span>{{ timeAgo(alert.created_at) }}</span>
                                    <span v-if="alert.link" class="inline-flex items-center gap-1 text-brand-600">
                                        <ExternalLink class="w-3 h-3" />
                                        {{ __('Open') }}
                                    </span>
                                </div>
                            </div>
                            <button
                                type="button"
                                class="opacity-0 group-hover:opacity-100 transition-opacity p-1 rounded-sm
                                       text-ink-400 hover:text-danger-600 hover:bg-paper-100"
                                :aria-label="__('Delete notification')"
                                @click.stop="destroy(alert.id)"
                            >
                                <Trash2 class="w-3.5 h-3.5" />
                            </button>
                        </div>
                        <span
                            v-if="!alert.read_at"
                            class="absolute start-2 top-1/2 -translate-y-1/2 w-1.5 h-1.5 rounded-full bg-brand-600"
                        />
                    </li>
                </ul>
            </div>
        </SheetContent>
    </Sheet>
</template>
