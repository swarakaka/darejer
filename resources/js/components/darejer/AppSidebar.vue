<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { usePage, Link } from '@inertiajs/vue3'
import {
    Layers, LayoutDashboard, Package, Users, Settings, LifeBuoy,
    BookOpen, BarChart3, BarChart2, Folders, ShoppingCart,
    FileText, Tag, Truck, Building, CreditCard, Calendar,
    Bell, Search, ChevronRight, ChevronDown, ChevronLeft,
    Inbox, Star, Archive, Globe, Shield, Wrench, Database,
    HelpCircle, PanelLeftClose, PanelLeftOpen,
} from 'lucide-vue-next'
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip'
import { usePermissions } from '@/composables/usePermissions'
import type { DarejerSharedProps, NavItem } from '@/types/darejer'

const STORAGE_KEY = 'darejer-sidebar-collapsed'

const page = usePage<DarejerSharedProps>()
const { can, isSuperAdmin } = usePermissions()

const collapsed = ref(false)

onMounted(() => {
    const stored = localStorage.getItem(STORAGE_KEY)
    if (stored !== null) {
        collapsed.value = stored === '1'
    }

    // Auto-expand parent groups that contain an active child
    for (const item of navItems.value) {
        if (item.children?.length && isGroupActive(item)) {
            expandedGroups.value.add(item.label)
        }
    }
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
    return currentPath.value === pathFromUrl(item.url)
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
        return
    }

    e.preventDefault()

    if (collapsed.value) {
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
    switch (color) {
        case 'success': return 'bg-success-500 text-white'
        case 'warning': return 'bg-warning-500 text-white'
        case 'danger':  return 'bg-danger-500  text-white'
        case 'info':
        default:        return 'bg-brand-500   text-white'
    }
}
</script>

<template>
    <div class="relative flex shrink-0">
        <TooltipProvider :delay-duration="120">
            <aside
                class="flex flex-col shrink-0 relative z-20 transition-[width] duration-200 ease-in-out"
                :style="{
                    width: collapsed ? 'var(--sidebar-width)' : 'var(--sidebar-expanded-width, 15rem)',
                    background: 'var(--sidebar-bg)',
                    borderRight: '1px solid var(--sidebar-border)',
                }"
            >
                <!-- Header -->
                <div
                    class="flex items-center shrink-0 border-b gap-2.5 overflow-hidden"
                    :class="collapsed ? 'justify-center' : 'px-3'"
                    :style="{
                        height: 'var(--topbar-height)',
                        borderColor: 'var(--sidebar-border)',
                    }"
                >
                    <div class="w-7 h-7 rounded-sm bg-brand-600 flex items-center justify-center shrink-0">
                        <span class="font-serif text-white text-base leading-none translate-y-[1px]">D</span>
                    </div>
                    <span
                        v-if="!collapsed"
                        class="text-sm font-semibold text-ink-100 truncate"
                    >Darejer</span>
                </div>

                <!-- Nav -->
                <nav class="flex-1 flex flex-col py-2 overflow-y-auto overflow-x-hidden">
                    <template v-for="item in navItems" :key="item.label">
                        <!-- ── Collapsed: icon-only with tooltip ── -->
                        <template v-if="collapsed">
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Link
                                        :href="item.url ?? '#'"
                                        class="relative flex items-center justify-center h-11 mx-1.5 my-0.5 rounded-sm transition-colors duration-120 no-underline"
                                        :class="isGroupActive(item) || (activeGroup?.label === item.label && flyoutOpen)
                                            ? 'text-brand-200 bg-[color:var(--sidebar-item-bg-active)]'
                                            : 'text-ink-200 hover:!text-brand-300 hover:bg-brand-500/15'"
                                        @click="(e) => onItemClick(item, e)"
                                    >
                                        <span
                                            v-if="isGroupActive(item) || (activeGroup?.label === item.label && flyoutOpen)"
                                            class="absolute start-0 top-2 bottom-2 w-[2px] rounded-full bg-brand-400"
                                        />
                                        <component :is="getIcon(item.icon)" class="w-[18px] h-[18px]" />

                                        <span
                                            v-if="item.badge"
                                            class="absolute top-1.5 end-1.5 inline-flex items-center justify-center min-w-[1.125rem] h-4 px-1 rounded-full text-[9px] font-bold leading-none tabular-nums"
                                            :class="badgeClass(item.badgeColor)"
                                        >
                                            {{ item.badge }}
                                        </span>

                                        <span
                                            v-if="item.children?.length"
                                            class="absolute bottom-1 end-1 w-1 h-1 rounded-full bg-brand-300/70"
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
                                class="relative flex items-center gap-2.5 h-9 mx-1.5 my-0.5 px-2.5 rounded-sm transition-colors duration-120 no-underline overflow-hidden"
                                :class="isGroupActive(item)
                                    ? 'text-brand-200 bg-[color:var(--sidebar-item-bg-active)]'
                                    : 'text-ink-200 hover:!text-brand-300 hover:bg-brand-500/15'"
                                @click="(e) => onItemClick(item, e)"
                            >
                                <span
                                    v-if="isGroupActive(item)"
                                    class="absolute start-0 top-1.5 bottom-1.5 w-[2px] rounded-full bg-brand-400"
                                />
                                <component :is="getIcon(item.icon)" class="w-[18px] h-[18px] shrink-0" />
                                <span class="flex-1 text-sm truncate">{{ item.label }}</span>

                                <span
                                    v-if="item.badge"
                                    class="inline-flex items-center justify-center min-w-[1.125rem] h-4 px-1 rounded-full text-[9px] font-bold leading-none tabular-nums"
                                    :class="badgeClass(item.badgeColor)"
                                >
                                    {{ item.badge }}
                                </span>

                                <component
                                    v-if="item.children?.length"
                                    :is="expandedGroups.has(item.label) ? ChevronDown : ChevronRight"
                                    class="w-3.5 h-3.5 shrink-0 text-ink-400"
                                />
                            </Link>

                            <!-- Inline children (expanded mode) -->
                            <div
                                v-if="item.children?.length && expandedGroups.has(item.label)"
                                class="flex flex-col"
                            >
                                <template v-for="(child, i) in item.children" :key="child.label">
                                    <div
                                        v-if="child.group && (i === 0 || (item.children?.[i - 1]?.group !== child.group))"
                                        class="ps-10 pt-2 pb-0.5 text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-400 select-none"
                                    >
                                        {{ child.group }}
                                    </div>

                                    <Link
                                        :href="child.url ?? '#'"
                                        class="flex items-center gap-2 h-7 ps-10 pe-3 text-[13px] no-underline transition-colors duration-100"
                                        :class="isActive(child)
                                            ? 'text-brand-300 font-medium'
                                            : 'text-ink-300 hover:text-brand-300'"
                                    >
                                        <component
                                            v-if="child.icon"
                                            :is="getIcon(child.icon)"
                                            class="w-3.5 h-3.5 shrink-0 text-ink-400"
                                        />
                                        <span class="flex-1 truncate">{{ child.label }}</span>

                                        <span
                                            v-if="child.badge"
                                            class="inline-flex items-center justify-center min-w-[1.125rem] h-4 px-1 rounded-full text-[9px] font-bold leading-none tabular-nums"
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
                    class="flex items-center py-2 gap-0.5 border-t"
                    :class="collapsed ? 'flex-col' : 'px-1.5 justify-between'"
                    :style="{ borderColor: 'var(--sidebar-border)' }"
                >
                    <div :class="collapsed ? 'flex flex-col items-center gap-0.5' : 'flex items-center gap-0.5'">
                        <Tooltip>
                            <TooltipTrigger as-child>
                                <button
                                    type="button"
                                    class="flex items-center justify-center w-9 h-9 rounded-sm text-ink-200 hover:!text-brand-300 hover:bg-brand-500/15 transition-colors"
                                >
                                    <LifeBuoy class="w-[18px] h-[18px]" />
                                </button>
                            </TooltipTrigger>
                            <TooltipContent side="right" :side-offset="10" class="text-xs">Help</TooltipContent>
                        </Tooltip>
                        <Tooltip v-if="can('darejer.settings') || isSuperAdmin">
                            <TooltipTrigger as-child>
                                <button
                                    type="button"
                                    class="flex items-center justify-center w-9 h-9 rounded-sm text-ink-200 hover:!text-brand-300 hover:bg-brand-500/15 transition-colors"
                                >
                                    <Settings class="w-[18px] h-[18px]" />
                                </button>
                            </TooltipTrigger>
                            <TooltipContent side="right" :side-offset="10" class="text-xs">Settings</TooltipContent>
                        </Tooltip>
                    </div>

                    <!-- Collapse/Expand toggle -->
                    <Tooltip>
                        <TooltipTrigger as-child>
                            <button
                                type="button"
                                class="flex items-center justify-center w-9 h-9 rounded-sm text-ink-200 hover:!text-brand-300 hover:bg-brand-500/15 transition-colors"
                                @click="toggleCollapsed"
                            >
                                <component :is="collapsed ? PanelLeftOpen : PanelLeftClose" class="w-[18px] h-[18px]" />
                            </button>
                        </TooltipTrigger>
                        <TooltipContent side="right" :side-offset="10" class="text-xs">
                            {{ collapsed ? 'Expand sidebar' : 'Collapse sidebar' }}
                        </TooltipContent>
                    </Tooltip>
                </div>
            </aside>
        </TooltipProvider>

        <!-- Flyout panel for items with children (collapsed mode only) -->
        <transition
            enter-active-class="transition-all duration-150 ease-out"
            enter-from-class="opacity-0 -translate-x-2"
            enter-to-class="opacity-100 translate-x-0"
            leave-active-class="transition-all duration-100 ease-in"
            leave-from-class="opacity-100 translate-x-0"
            leave-to-class="opacity-0 -translate-x-2"
        >
            <div
                v-if="collapsed && flyoutOpen && activeGroup"
                class="absolute start-full top-0 h-full z-10 bg-white border-s border-paper-200 flex flex-col"
                :style="{ width: 'var(--flyout-width)' }"
            >
                <!-- Flyout header -->
                <div
                    class="flex items-center justify-between px-4 border-b border-paper-200 shrink-0"
                    :style="{ height: 'var(--topbar-height)' }"
                >
                    <span class="text-sm font-semibold tracking-tight text-ink-800">{{ activeGroup.label }}</span>
                    <button
                        type="button"
                        class="text-ink-400 hover:text-ink-700 transition-colors rtl:rotate-180"
                        @click="closeFlyout"
                    >
                        <ChevronLeft class="w-4 h-4" />
                    </button>
                </div>

                <!-- Flyout nav items -->
                <nav class="flex-1 flex flex-col py-1.5 overflow-y-auto">
                    <template v-for="(child, i) in activeGroup.children" :key="child.label">
                        <div
                            v-if="child.group && (i === 0 || (activeGroup.children?.[i - 1]?.group !== child.group))"
                            class="px-4 pt-3 pb-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-400 select-none"
                        >
                            {{ child.group }}
                        </div>

                        <Link
                            :href="child.url ?? '#'"
                            class="flex items-center gap-2.5 h-8 px-4 text-sm no-underline transition-colors duration-100 border-s-2"
                            :class="isActive(child)
                                ? 'bg-brand-50 text-brand-700 font-medium border-brand-500'
                                : 'text-ink-600 hover:bg-paper-75 hover:text-ink-900 border-transparent'"
                            @click="closeFlyout"
                        >
                            <component
                                v-if="child.icon"
                                :is="getIcon(child.icon)"
                                class="w-3.5 h-3.5 shrink-0 text-ink-400"
                            />
                            <span class="flex-1 truncate">{{ child.label }}</span>

                            <span
                                v-if="child.badge"
                                class="inline-flex items-center justify-center min-w-[1.125rem] h-4 px-1 rounded-full text-[9px] font-bold leading-none tabular-nums"
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
