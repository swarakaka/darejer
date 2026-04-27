<script setup lang="ts">
import { ref, computed } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import { Button }      from '@/components/ui/button'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu'
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from '@/components/ui/dialog'
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip'
import {
    Save, X, Trash2, ChevronDown, ExternalLink,
    Plus, Pencil, Eye, MoreHorizontal, Zap, Download, AlertTriangle,
} from 'lucide-vue-next'
import { evaluateDependOn } from '@/composables/useDependOn'
import useTranslation       from '@/composables/useTranslation'
import type { DarejerAction } from '@/types/darejer'

const { __ } = useTranslation()

type ButtonVariant = 'default' | 'destructive' | 'outline' | 'ghost' | 'secondary' | 'link'

const props = defineProps<{
    actions:     DarejerAction[]
    placement?:  'header' | 'footer' | 'dialog' | 'row'
    selected?:   (string | number)[]
    // When a Screen wraps these actions, it passes in its form. Save and Cancel
    // then run against the form instead of navigating.
    onSave?:     () => void
    onCancel?:   (cancelUrl?: string) => void
    processing?: boolean
    isDirty?:    boolean
    // Live form data for client-side dependOn evaluation.
    formData?:   Record<string, unknown>
    // Fired after a BulkAction post succeeds — the DataTable uses it to clear
    // the selected set so the strip collapses.
    onBulkSuccess?: () => void
}>()

const visibleActions = computed(() =>
    props.actions.filter(a => evaluateDependOn(a.dependOn, props.formData ?? {})),
)

const confirmOpen   = ref(false)
const confirmAction = ref<DarejerAction | null>(null)

function requestConfirm(action: DarejerAction) {
    confirmAction.value = action
    confirmOpen.value   = true
}

function executeConfirmed() {
    if (confirmAction.value) executeAction(confirmAction.value, true)
    confirmOpen.value   = false
    confirmAction.value = null
}

const iconMap: Record<string, unknown> = {
    Save, X, Trash2, ChevronDown, ExternalLink,
    Plus, Pencil, Eye, MoreHorizontal, Zap, Download,
}
function resolveIcon(name?: string) {
    return name ? (iconMap[name] ?? null) : null
}

function buttonClasses(action: DarejerAction): string {
    const base = 'inline-flex items-center gap-1.5 text-sm h-8 px-3 rounded-sm border transition-colors'
    if (action.type === 'Save') {
        return `${base} bg-brand-600 hover:bg-brand-700 text-white border-transparent`
    }
    if (action.variant === 'destructive') {
        return `${base} bg-white hover:bg-danger-50 text-danger-700 border-danger-200`
    }
    return `${base} bg-white hover:bg-paper-100 text-ink-700 border-paper-300`
}

function resolveVariant(action: DarejerAction): ButtonVariant {
    return (action.variant as ButtonVariant | undefined) ?? 'outline'
}

function executeAction(action: DarejerAction, skipConfirm = false) {
    if (!skipConfirm && action.confirm) {
        requestConfirm(action)
        return
    }

    // Save routes through the parent form when wired.
    if (action.type === 'Save' && props.onSave) {
        props.onSave()
        return
    }

    // Cancel routes through the parent form when wired; otherwise falls back
    // to history.back() for dialogs or navigation for full-page screens.
    if (action.type === 'Cancel') {
        if (props.onCancel) {
            props.onCancel(action.url)
            return
        }
        const inDialog = typeof window !== 'undefined'
            && new URL(window.location.href).searchParams.get('_dialog') === '1'
        if (inDialog) {
            window.history.back()
            return
        }
    }

    if (action.type === 'BulkAction') {
        const target = action.batchUrl ?? action.url
        if (!target) return
        router.post(
            target,
            { [action.batchParam ?? 'ids']: props.selected ?? [] },
            { onSuccess: () => props.onBulkSuccess?.() },
        )
        return
    }

    if (!action.url) return

    if (action.external) {
        window.open(action.url, '_blank')
        return
    }

    const method = (action.method ?? 'GET').toLowerCase()

    if (method === 'get') {
        router.visit(action.url, {
            data: action.dialog ? { _dialog: '1' } : {},
        })
        return
    }

    router.visit(action.url, {
        method: method as 'post' | 'put' | 'patch' | 'delete',
    })
}

const placementClass = computed(() => {
    switch (props.placement) {
        case 'footer': return 'justify-end'
        case 'dialog': return 'justify-end'
        case 'row':    return ''
        default:       return ''
    }
})
</script>

<template>
    <TooltipProvider :delay-duration="0">
        <div class="flex items-center gap-1.5 flex-wrap" :class="placementClass">

            <template v-for="action in visibleActions" :key="action.label">

                <!-- Link action -->
                <Tooltip v-if="action.type === 'Link'" :disabled="!action.tooltip">
                    <TooltipTrigger as-child>
                        <a
                            v-if="action.external"
                            :href="action.url ?? '#'"
                            target="_blank"
                            rel="noopener"
                            class="inline-flex items-center gap-1.5 text-sm text-brand-600
                                   no-underline hover:text-brand-700 h-8 px-2"
                        >
                            <component :is="resolveIcon(action.icon)" v-if="action.icon" class="w-3.5 h-3.5" />
                            {{ action.label }}
                            <ExternalLink class="w-3 h-3 opacity-60" />
                        </a>
                        <Link
                            v-else
                            :href="action.url ?? '#'"
                            class="inline-flex items-center gap-1.5 text-sm text-brand-600
                                   no-underline hover:text-brand-700 h-8 px-2"
                        >
                            <component :is="resolveIcon(action.icon)" v-if="action.icon" class="w-3.5 h-3.5" />
                            {{ action.label }}
                        </Link>
                    </TooltipTrigger>
                    <TooltipContent v-if="action.tooltip">{{ action.tooltip }}</TooltipContent>
                </Tooltip>

                <!-- Dropdown action -->
                <DropdownMenu v-else-if="action.type === 'Dropdown'">
                    <DropdownMenuTrigger as-child>
                        <button
                            type="button"
                            :disabled="action.disabled"
                            :class="buttonClasses(action)"
                        >
                            <component :is="resolveIcon(action.icon)" v-if="action.icon" class="w-3.5 h-3.5" />
                            {{ action.label }}
                            <ChevronDown class="w-3 h-3 opacity-70" />
                        </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <template v-for="item in (action.items ?? [])" :key="item.label">
                            <DropdownMenuSeparator v-if="item.type === 'Separator'" />
                            <DropdownMenuItem
                                v-else
                                :disabled="item.disabled"
                                class="text-sm gap-2 cursor-pointer"
                                :class="item.variant === 'destructive' ? 'text-danger-700' : ''"
                                @click="executeAction(item)"
                            >
                                <component :is="resolveIcon(item.icon)" v-if="item.icon" class="w-3.5 h-3.5" />
                                {{ item.label }}
                            </DropdownMenuItem>
                        </template>
                    </DropdownMenuContent>
                </DropdownMenu>

                <!-- Button-style action -->
                <Tooltip v-else :disabled="!action.tooltip">
                    <TooltipTrigger as-child>
                        <button
                            type="button"
                            :disabled="action.disabled || (action.type === 'Save' && processing)"
                            :class="[
                                buttonClasses(action),
                                action.fullWidth ? 'w-full justify-center' : '',
                                'disabled:opacity-50 disabled:cursor-not-allowed',
                            ]"
                            @click="executeAction(action)"
                        >
                            <span
                                v-if="action.type === 'Save' && processing"
                                class="inline-block w-3.5 h-3.5 border-2 border-white/40 border-t-white rounded-full animate-spin"
                            />
                            <component
                                v-else-if="action.icon"
                                :is="resolveIcon(action.icon)"
                                class="w-3.5 h-3.5"
                            />
                            {{ action.type === 'Save' && processing ? __('Saving') : action.label }}
                        </button>
                    </TooltipTrigger>
                    <TooltipContent v-if="action.tooltip">{{ action.tooltip }}</TooltipContent>
                </Tooltip>

            </template>

        </div>

        <!-- Confirmation dialog -->
        <Dialog :open="confirmOpen" @update:open="confirmOpen = $event">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <div class="flex items-start gap-3">
                        <div class="w-9 h-9 rounded-sm bg-danger-50 border border-danger-100
                                    flex items-center justify-center shrink-0">
                            <AlertTriangle class="w-4 h-4 text-danger-600" />
                        </div>
                        <div class="flex-1">
                            <DialogTitle class="font-serif text-xl leading-tight">
                                {{ __('Confirm action') }}
                            </DialogTitle>
                            <DialogDescription class="text-sm text-ink-500 mt-1.5 leading-relaxed">
                                {{ confirmAction?.confirm }}
                            </DialogDescription>
                        </div>
                    </div>
                </DialogHeader>
                <DialogFooter class="flex justify-end gap-2">
                    <button
                        type="button"
                        class="inline-flex items-center h-8 px-3 text-sm text-ink-700
                               bg-white border border-paper-300 hover:bg-paper-100 rounded-sm transition-colors"
                        @click="confirmOpen = false"
                    >
                        {{ __('Cancel') }}
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center h-8 px-3 text-sm text-white
                               bg-danger-600 hover:bg-danger-700 border border-transparent rounded-sm transition-colors"
                        @click="executeConfirmed"
                    >
                        {{ confirmAction?.label }}
                    </button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

    </TooltipProvider>
</template>
