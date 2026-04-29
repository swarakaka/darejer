<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { usePage, Link } from '@inertiajs/vue3'
import {
    Layers, LayoutDashboard, Package, Users, Settings, LifeBuoy,
    BookOpen, BarChart3, BarChart2, Folders, ShoppingCart,
    FileText, Tag, Truck, Building, CreditCard, Calendar,
    Bell, Search, ChevronRight, ChevronDown, ChevronLeft,
    Inbox, Star, Archive, Globe, Shield, Wrench, Database,
    HelpCircle, PanelLeftClose, PanelLeftOpen, X,
} from 'lucide-vue-next'
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip'
import { usePermissions } from '@/composables/usePermissions'
import { useSidebar }      from '@/composables/useSidebar'
import useTranslation     from '@/composables/useTranslation'
import type { DarejerSharedProps, NavItem } from '@/types/darejer'

const { __ } = useTranslation()

const STORAGE_KEY = 'darejer-sidebar-collapsed'
const MOBILE_QUERY = '(max-width: 767px)'

const page = usePage<DarejerSharedProps>()
const { can, isSuperAdmin } = usePermissions()
const { mobileOpen, closeMobile } = useSidebar()

const collapsed = ref(false)
const isMobile  = ref(false)
let mediaQuery: MediaQueryList | null = null

// On mobile we always render the expanded sidebar — the desktop "collapsed"
// preference is irrelevant inside an off-canvas drawer.
const effectiveCollapsed = computed(() => !isMobile.value && collapsed.value)

function syncMobile(matches: boolean) {
    isMobile.value = matches
    if (!matches) {
        // Resizing back to desktop: drop any open drawer state and flyout artefacts.
        closeMobile()
    }
}

function handleMediaChange(e: MediaQueryListEvent) {
    syncMobile(e.matches)
}

onMounted(() => {
    const stored = localStorage.getItem(STORAGE_KEY)
    if (stored !== null) {
        collapsed.value = stored === '1'
    }

    if (typeof window !== 'undefined' && typeof window.matchMedia === 'function') {
        mediaQuery = window.matchMedia(MOBILE_QUERY)
        syncMobile(mediaQuery.matches)
        mediaQuery.addEventListener('change', handleMediaChange)
    }

    // Auto-expand parent groups that contain an active child
    for (const item of navItems.value) {
        if (item.children?.length && isGroupActive(item)) {
            expandedGroups.value.add(item.label)
        }
    }
})

onBeforeUnmount(() => {
    mediaQuery?.removeEventListener('change', handleMediaChange)
})

// Lock body scroll while the mobile drawer is open.
watch(mobileOpen, (open) => {
    if (typeof document === 'undefined') return
    document.body.style.overflow = open && isMobile.value ? 'hidden' : ''
})

function toggleCollapsed() {
    collapsed.value = !collapsed.value
    localStorage.setItem(STORAGE_KEY, collapsed.value ? '1' : '0')
    if (!collapsed.value) closeFlyout()
}

const navItems = computed((): NavItem[] => {
    const items = page.props.navigation ?? []
    if (items.length) return items
    return [
        { label: 'Dashboard', icon: 'LayoutDashboard', url: route('darejer.dashboard').toString() },
    ]
})

const iconMap: Record<string, unknown> = {
    Layers, LayoutDashboard, Package, Users, Settings, LifeBuoy,
    BookOpen, BarChart3, BarChart2, Folders, ShoppingCart,
    FileText, Tag, Truck, Building, CreditCard, Calendar,
    Bell, Search, ChevronRight, ChevronDown, ChevronLeft,
    Inbox, Star, Archive, Globe, Shield, Wrench, Database,
    HelpCircle,
}
function getIcon(name?: string) {
    return name ? (iconMap[name] ?? LayoutDashboard) : LayoutDashboard
}

const currentPath = computed(() => {
    const u = page.url ?? '/'
    const q = u.indexOf('?')
    return q === -1 ? u : u.slice(0, q)
})

function pathFromUrl(url: string): string {
    if (url.startsWith('/')) return url
    try {
        return new URL(url, window.location.origin).pathname
    } catch {
        return url
    }
}

function isActive(item: NavItem): boolean {
    if (!item.url) return false
    const navPath = pathFromUrl(item.url)
    if (navPath === '/') return currentPath.value === '/'
    return currentPath.value === navPath
        || currentPath.value.startsWith(navPath + '/')
}

function isGroupActive(item: NavItem): boolean {
    return isActive(item) || (item.children ?? []).some(c => isActive(c))
}

// ── Flyout state (collapsed mode) ────────────────────────────────
const flyoutOpen  = ref(false)
const activeGroup = ref<NavItem | null>(null)

// ── Expanded inline children toggle ──────────────────────────────
const expandedGroups = ref<Set<string>>(new Set())

function onItemClick(item: NavItem, e: Event) {
    if (!item.children?.length) {
        closeFlyout()
        closeMobile()
        return
    }

    e.preventDefault()

    if (effectiveCollapsed.value) {
        if (activeGroup.value?.label === item.label && flyoutOpen.value) {
            closeFlyout()
        } else {
            activeGroup.value = item
            flyoutOpen.value  = true
        }
    } else {
        const key = item.label
        if (expandedGroups.value.has(key)) {
            expandedGroups.value.delete(key)
        } else {
            expandedGroups.value.clear()
            expandedGroups.value.add(key)
        }
    }
}

function closeFlyout() {
    flyoutOpen.value  = false
    activeGroup.value = null
}

function badgeClass(color?: string): string {
    const base = 'rounded-none'
    switch (color) {
        case 'success': return `${base} bg-success-500 text-white`
        case 'warning': return `${base} bg-warning-500 text-white`
        case 'danger':  return `${base} bg-danger-500  text-white`
        case 'info':
        default:        return `${base} bg-brand-500   text-white`
    }
}
</script>

<template>
    <div class="md:relative md:flex md:shrink-0">
        <!-- Mobile backdrop -->
        <transition
            enter-active-class="transition-opacity duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="mobileOpen && isMobile"
                class="md:hidden fixed inset-0 z-30 bg-ink-950/50 backdrop-blur-sm"
                @click="closeMobile"
            />
        </transition>

        <TooltipProvider :delay-duration="120">
            <aside
                class="flex flex-col shrink-0 fixed md:static inset-y-0 start-0 z-40
                       bg-(--sidebar-bg) border-e border-(--sidebar-border)
                       transition-[transform,width] duration-200 ease-in-out
                       md:translate-x-0 md:rtl:translate-x-0"
                :class="[
                    mobileOpen
                        ? 'translate-x-0 rtl:translate-x-0'
                        : '-translate-x-full rtl:translate-x-full',
                    effectiveCollapsed ? 'w-(--sidebar-width)' : 'w-(--sidebar-expanded-width)',
                ]"
            >
                <!-- Header -->
                <div
                    class="flex items-center shrink-0 h-(--topbar-height) gap-2.5 overflow-hidden"
                    :class="effectiveCollapsed ? 'justify-center' : 'px-3'"
                >
                    <div class="w-6 h-6 flex items-center justify-center shrink-0">
                        <svg viewBox="0 0 18 18" class="w-[18px] h-[18px]" aria-hidden="true">
                            <path fill="#50e6ff" d="M10.61 1.31l4.4 11.27-3.04 3.41h-7.31z"/>
                            <path fill="#0078d4" d="M11.34 1.31h6.06l-3.84 11.27z"/>
                            <path fill="#0078d4" d="M.6 16l4.7-1.83 9.61.83z"/>
                        </svg>
                    </div>
                    <div v-if="!effectiveCollapsed" class="flex items-baseline gap-1.5 leading-tight min-w-0">
                        <span class="text-[13px] font-semibold text-white truncate tracking-tight">Darejer</span>
                        <span class="text-[10px] text-paper-300 truncate">{{ __('Enterprise') }}</span>
                    </div>
                    <button
                        v-if="isMobile"
                        type="button"
                        class="md:hidden ms-auto flex items-center justify-center w-7 h-7 rounded-none text-paper-200 hover:text-white hover:bg-white/10 transition-colors"
                        :aria-label="__('Close menu')"
                        @click="closeMobile"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <!-- Nav -->
                <nav class="flex-1 flex flex-col py-1 overflow-y-auto overflow-x-hidden">
                    <template v-for="item in navItems" :key="item.label">
                        <!-- ── Collapsed: icon-only with tooltip ── -->
                        <template v-if="effectiveCollapsed">
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Link
                                        :href="item.url ?? '#'"
                                        class="relative flex items-center justify-center h-9 transition-colors duration-100 no-underline"
                                        :class="isGroupActive(item) || (activeGroup?.label === item.label && flyoutOpen)
                                            ? 'text-white bg-[color:var(--sidebar-item-bg-active)]'
                                            : 'text-paper-200 hover:text-white hover:bg-white/[0.06]'"
                                        @click="(e) => onItemClick(item, e)"
                                    >
                                        <span
                                            v-if="isGroupActive(item) || (activeGroup?.label === item.label && flyoutOpen)"
                                            class="absolute start-0 top-0 bottom-0 w-[2px] bg-brand-400"
                                        />
                                        <component :is="getIcon(item.icon)" class="w-4 h-4" />

                                        <span
                                            v-if="item.badge"
                                            class="absolute top-1 end-1 inline-flex items-center justify-center min-w-[1.125rem] h-4 px-1 text-[9px] font-bold leading-none tabular-nums"
                                            :class="badgeClass(item.badgeColor)"
                                        >
                                            {{ item.badge }}
                                        </span>

                                        <span
                                            v-if="item.children?.length"
                                            class="absolute bottom-1 end-1 w-1 h-1 bg-brand-400/80"
                                        />
                                    </Link>
                                </TooltipTrigger>
                                <TooltipContent
                                    side="right"
                                    class="text-xs font-medium tracking-wide"
                                    :side-offset="10"
                                >
                                    {{ item.label }}
                                </TooltipContent>
                            </Tooltip>
                        </template>

                        <!-- ── Expanded: icon + label ── -->
                        <template v-else>
                            <Link
                                :href="item.url ?? '#'"
                                class="relative flex items-center gap-3 h-9 px-3 transition-colors duration-100 no-underline overflow-hidden"
                                :class="isGroupActive(item)
                                    ? 'text-white bg-[color:var(--sidebar-item-bg-active)]'
                                    : 'text-paper-200 hover:text-white hover:bg-white/[0.06]'"
                                @click="(e) => onItemClick(item, e)"
                            >
                                <span
                                    v-if="isGroupActive(item)"
                                    class="absolute inset-s-0 top-0 bottom-0 w-[2px] bg-brand-400"
                                />
                                <component :is="getIcon(item.icon)" class="w-4 h-4 shrink-0" />
                                <span class="flex-1 text-[13px] font-normal truncate">{{ item.label }}</span>

                                <span
                                    v-if="item.badge"
                                    class="inline-flex items-center justify-center min-w-[1.125rem] h-4 px-1 text-[9px] font-bold leading-none tabular-nums"
                                    :class="badgeClass(item.badgeColor)"
                                >
                                    {{ item.badge }}
                                </span>

                                <component
                                    v-if="item.children?.length"
                                    :is="expandedGroups.has(item.label) ? ChevronDown : ChevronRight"
                                    class="w-3.5 h-3.5 shrink-0 text-paper-300 rtl:[&:not(.lucide-chevron-down)]:rotate-180"
                                />
                            </Link>

                            <!-- Inline children (expanded mode) -->
                            <div
                                v-if="item.children?.length && expandedGroups.has(item.label)"
                                class="flex flex-col bg-black/20"
                            >
                                <template v-for="(child, i) in item.children" :key="child.label">
                                    <div
                                        v-if="child.group && (i === 0 || (item.children?.[i - 1]?.group !== child.group))"
                                        class="ps-10 pt-2 pb-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-paper-300/70 select-none"
                                    >
                                        {{ child.group }}
                                    </div>

                                    <Link
                                        :href="child.url ?? '#'"
                                        class="relative flex items-center gap-2 h-8 ps-10 pe-3 text-[12.5px] no-underline transition-colors duration-100"
                                        :class="isActive(child)
                                            ? 'text-white font-medium bg-white/[0.04]'
                                            : 'text-paper-200 hover:text-white hover:bg-white/[0.04]'"
                                    >
                                        <span
                                            v-if="isActive(child)"
                                            class="absolute inset-s-0 top-0 bottom-0 w-[2px] bg-brand-400"
                                        />
                                        <component
                                            v-if="child.icon"
                                            :is="getIcon(child.icon)"
                                            class="w-3.5 h-3.5 shrink-0 text-paper-300"
                                        />
                                        <span class="flex-1 truncate">{{ child.label }}</span>

                                        <span
                                            v-if="child.badge"
                                            class="inline-flex items-center justify-center min-w-[1.125rem] h-4 px-1 text-[9px] font-bold leading-none tabular-nums"
                                            :class="badgeClass(child.badgeColor)"
                                        >
                                            {{ child.badge }}
                                        </span>
                                    </Link>
                                </template>
                            </div>
                        </template>
                    </template>
                </nav>

                <!-- Footer -->
                <div
                    class="flex items-center border-t border-(--sidebar-border)"
                    :class="effectiveCollapsed ? 'flex-col' : 'justify-between'"
                >
                    <div class="flex" :class="effectiveCollapsed ? 'flex-col' : 'flex-row'">
                        <Tooltip>
                            <TooltipTrigger as-child>
                                <button
                                    type="button"
                                    class="flex items-center justify-center w-9 h-9 text-paper-200 hover:text-white hover:bg-white/[0.06] transition-colors"
                                >
                                    <LifeBuoy class="w-4 h-4" />
                                </button>
                            </TooltipTrigger>
                            <TooltipContent side="right" :side-offset="10" class="text-xs">{{ __('Help') }}</TooltipContent>
                        </Tooltip>
                        <Tooltip v-if="can('darejer.settings') || isSuperAdmin">
                            <TooltipTrigger as-child>
                                <button
                                    type="button"
                                    class="flex items-center justify-center w-9 h-9 text-paper-200 hover:text-white hover:bg-white/[0.06] transition-colors"
                                >
                                    <Settings class="w-4 h-4" />
                                </button>
                            </TooltipTrigger>
                            <TooltipContent side="right" :side-offset="10" class="text-xs">{{ __('Settings') }}</TooltipContent>
                        </Tooltip>
                    </div>

                    <!-- Collapse/Expand toggle (desktop only) -->
                    <Tooltip v-if="!isMobile">
                        <TooltipTrigger as-child>
                            <button
                                type="button"
                                class="flex items-center justify-center w-9 h-9 text-paper-200 hover:text-white hover:bg-white/[0.06] transition-colors"
                                @click="toggleCollapsed"
                            >
                                <component :is="collapsed ? PanelLeftOpen : PanelLeftClose" class="w-4 h-4 rtl:rotate-180" />
                            </button>
                        </TooltipTrigger>
                        <TooltipContent side="right" :side-offset="10" class="text-xs">
                            {{ collapsed ? __('Expand sidebar') : __('Collapse sidebar') }}
                        </TooltipContent>
                    </Tooltip>
                </div>
            </aside>
        </TooltipProvider>

        <!-- Flyout panel for items with children (collapsed mode only) -->
        <transition
            enter-active-class="transition-all duration-150 ease-out"
            enter-from-class="opacity-0 -translate-x-2 rtl:translate-x-2"
            enter-to-class="opacity-100 translate-x-0"
            leave-active-class="transition-all duration-100 ease-in"
            leave-from-class="opacity-100 translate-x-0"
            leave-to-class="opacity-0 -translate-x-2 rtl:translate-x-2"
        >
            <div
                v-if="effectiveCollapsed && flyoutOpen && activeGroup"
                class="absolute start-full top-0 h-full z-10 w-(--flyout-width) bg-white border-s border-paper-200 shadow-[var(--shadow-blade)] flex flex-col"
            >
                <!-- Flyout header -->
                <div class="flex items-center justify-between h-(--topbar-height) px-4 border-b border-paper-200 shrink-0 bg-paper-75">
                    <span class="text-[13px] font-semibold tracking-tight text-ink-900">{{ activeGroup.label }}</span>
                    <button
                        type="button"
                        class="text-ink-500 hover:text-brand-600 transition-colors rtl:rotate-180"
                        @click="closeFlyout"
                    >
                        <ChevronLeft class="w-4 h-4" />
                    </button>
                </div>

                <!-- Flyout nav items -->
                <nav class="flex-1 flex flex-col py-1 overflow-y-auto">
                    <template v-for="(child, i) in activeGroup.children" :key="child.label">
                        <div
                            v-if="child.group && (i === 0 || (activeGroup.children?.[i - 1]?.group !== child.group))"
                            class="px-4 pt-3 pb-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-500 select-none"
                        >
                            {{ child.group }}
                        </div>

                        <Link
                            :href="child.url ?? '#'"
                            class="relative flex items-center gap-2.5 h-8 px-4 text-[13px] no-underline transition-colors duration-100"
                            :class="isActive(child)
                                ? 'bg-brand-50 text-brand-700 font-semibold'
                                : 'text-ink-700 hover:bg-paper-100 hover:text-ink-900'"
                            @click="closeFlyout"
                        >
                            <span
                                v-if="isActive(child)"
                                class="absolute inset-s-0 top-0 bottom-0 w-[2px] bg-brand-500"
                            />
                            <component
                                v-if="child.icon"
                                :is="getIcon(child.icon)"
                                class="w-3.5 h-3.5 shrink-0"
                                :class="isActive(child) ? 'text-brand-600' : 'text-ink-500'"
                            />
                            <span class="flex-1 truncate">{{ child.label }}</span>

                            <span
                                v-if="child.badge"
                                class="inline-flex items-center justify-center min-w-[1.125rem] h-4 px-1 text-[9px] font-bold leading-none tabular-nums"
                                :class="badgeClass(child.badgeColor)"
                            >
                                {{ child.badge }}
                            </span>
                        </Link>
                    </template>
                </nav>
            </div>
        </transition>

        <!-- Overlay to close flyout on outside click -->
        <div
            v-if="flyoutOpen"
            class="fixed inset-0 z-[9]"
            @click="closeFlyout"
        />
    </div>
</template>
