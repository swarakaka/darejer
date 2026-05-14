<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { usePage, Link } from '@inertiajs/vue3'
import {
  Layers,
  LayoutDashboard,
  Package,
  Users,
  Settings,
  LifeBuoy,
  BookOpen,
  BarChart3,
  BarChart2,
  Folders,
  ShoppingCart,
  FileText,
  Tag,
  Truck,
  Building,
  Building2,
  CreditCard,
  Calendar,
  Bell,
  Search,
  ChevronRight,
  ChevronDown,
  ChevronLeft,
  Inbox,
  Star,
  Archive,
  Globe,
  Shield,
  Wrench,
  Database,
  HelpCircle,
  X,
  Landmark,
  UserSquare,
  ClipboardCheck,
} from 'lucide-vue-next'
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip'
import { useSidebar } from '@/composables/useSidebar'
import useTranslation from '@/composables/useTranslation'
import type { DarejerSharedProps, NavItem } from '@/types/darejer'

const { __ } = useTranslation()

const page = usePage<DarejerSharedProps>()
const {
  mobileOpen,
  isMobile,
  effectiveCollapsed,
  collapsed,
  closeMobile,
  isGroupExpanded,
  toggleGroup,
  expandGroup,
} = useSidebar()

// Stable across locale switches — labels are translated, but URLs/routes
// (or, failing that, the first child URL) survive a locale change.
function groupKey(item: NavItem): string {
  if (item.url) return `url:${item.url}`
  if (item.route) return `route:${item.route}`
  const firstChildUrl = item.children?.find((c) => c.url)?.url
  if (firstChildUrl) return `child:${firstChildUrl}`
  return `label:${item.label}`
}

onMounted(() => {
  // Auto-expand parent groups that contain the active child, even if the
  // user has not opened that group yet this session.
  for (const item of navItems.value) {
    if (item.children?.length && isGroupActive(item)) {
      expandGroup(groupKey(item))
    }
  }
})

// Lock body scroll while the mobile drawer is open.
watch(mobileOpen, (open) => {
  if (typeof document === 'undefined') return
  document.body.style.overflow = open && isMobile.value ? 'hidden' : ''
})

// When the user expands the sidebar, dismiss any open flyout panel
watch(collapsed, (isCollapsed) => {
  if (!isCollapsed) closeFlyout()
})

const navItems = computed((): NavItem[] => {
  const items = page.props.navigation ?? []
  if (items.length) return items
  return [{ label: 'Home', icon: 'LayoutDashboard', url: route('darejer.home').toString() }]
})

const iconMap: Record<string, unknown> = {
  Layers,
  LayoutDashboard,
  Package,
  Users,
  Settings,
  LifeBuoy,
  BookOpen,
  BarChart3,
  BarChart2,
  Folders,
  ShoppingCart,
  FileText,
  Tag,
  Truck,
  Building,
  Building2,
  CreditCard,
  Calendar,
  Bell,
  Search,
  ChevronRight,
  ChevronDown,
  ChevronLeft,
  Inbox,
  Star,
  Archive,
  Globe,
  Shield,
  Wrench,
  Database,
  HelpCircle,
  Landmark,
  UserSquare,
  ClipboardCheck,
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

// All leaf-item URLs (flattened, including nested children) — used to ensure
// the longest matching nav URL wins, so e.g. Dashboard at `/darejer` does not
// stay active when the user is on `/darejer/sales/sales-invoices`.
const allNavPaths = computed((): string[] => {
  const paths: string[] = []
  const walk = (items: NavItem[]) => {
    for (const item of items) {
      if (item.url) paths.push(pathFromUrl(item.url))
      if (item.children?.length) walk(item.children)
    }
  }
  walk(navItems.value)
  return paths
})

function pathMatches(navPath: string, currentPath: string): boolean {
  if (navPath === '/') return currentPath === '/'
  return currentPath === navPath || currentPath.startsWith(navPath + '/')
}

function isActive(item: NavItem): boolean {
  if (!item.url) return false
  const navPath = pathFromUrl(item.url)
  if (!pathMatches(navPath, currentPath.value)) return false

  // If another nav URL is a more specific match for the current path,
  // defer to it. This makes `/darejer/sales/sales-invoices/123/edit`
  // light up Sales Invoices instead of Dashboard (`/darejer`).
  for (const other of allNavPaths.value) {
    if (other.length > navPath.length && pathMatches(other, currentPath.value)) {
      return false
    }
  }
  return true
}

function isGroupActive(item: NavItem): boolean {
  return isActive(item) || (item.children ?? []).some((c) => isActive(c))
}

// ── Flyout state (collapsed mode) ────────────────────────────────
const flyoutOpen = ref(false)
const activeGroup = ref<NavItem | null>(null)

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
      flyoutOpen.value = true
    }
  } else {
    toggleGroup(groupKey(item))
  }
}

function closeFlyout() {
  flyoutOpen.value = false
  activeGroup.value = null
}

function badgeClass(color?: string): string {
  const base = 'rounded-none'
  switch (color) {
    case 'success':
      return `${base} bg-success-500 text-white`
    case 'warning':
      return `${base} bg-warning-500 text-white`
    case 'danger':
      return `${base} bg-danger-500  text-white`
    case 'info':
    default:
      return `${base} bg-brand-500   text-white`
  }
}
</script>

<template>
  <div class="md:relative md:flex md:shrink-0 print:hidden">
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
        class="fixed inset-0 z-30 bg-ink-900/55 md:hidden"
        @click="closeMobile"
      />
    </transition>

    <TooltipProvider :delay-duration="120">
      <aside
        class="fixed inset-y-0 start-0 z-40 flex shrink-0 flex-col border-e border-(--sidebar-border) bg-(--sidebar-bg) transition-[transform,width] duration-200 ease-in-out md:static md:translate-x-0 md:rtl:translate-x-0"
        :class="[
          mobileOpen ? `translate-x-0 rtl:translate-x-0` : `-translate-x-full rtl:translate-x-full`,
          effectiveCollapsed ? 'w-(--sidebar-width)' : `w-(--sidebar-expanded-width)`,
        ]"
      >
        <!-- Header — only the close button on mobile; logo lives in the topbar -->
        <div
          v-if="isMobile"
          class="flex h-(--topbar-height) shrink-0 items-center gap-2.5 border-b border-(--sidebar-border) px-3"
        >
          <span class="truncate text-[13px] font-semibold text-white">{{ __('Menu') }}</span>
          <button
            type="button"
            class="ms-auto flex h-7 w-7 items-center justify-center rounded-[2px] text-[#e1dfdd] transition-colors hover:bg-white/[0.08] hover:text-white md:hidden"
            :aria-label="__('Close menu')"
            @click="closeMobile"
          >
            <X class="h-4 w-4" />
          </button>
        </div>

        <!-- Nav -->
        <nav class="scrollbar-darejer flex flex-1 flex-col overflow-x-hidden overflow-y-auto py-2">
          <template v-for="item in navItems" :key="item.label">
            <!-- ── Collapsed: icon-only with tooltip ── -->
            <template v-if="effectiveCollapsed">
              <Tooltip>
                <TooltipTrigger as-child>
                  <Link
                    :href="item.url ?? '#'"
                    class="relative flex h-9 items-center justify-center no-underline transition-colors duration-100"
                    :class="
                      isGroupActive(item) || (activeGroup?.label === item.label && flyoutOpen)
                        ? `bg-[color:var(--sidebar-item-bg-active)] text-white`
                        : `text-[#e1dfdd] hover:bg-white/[0.08] hover:text-white`
                    "
                    @click="(e) => onItemClick(item, e)"
                  >
                    <span
                      v-if="
                        isGroupActive(item) || (activeGroup?.label === item.label && flyoutOpen)
                      "
                      class="absolute start-0 top-0 bottom-0 w-[2px] bg-brand-500"
                    />
                    <component :is="getIcon(item.icon)" class="h-4 w-4" />

                    <span
                      v-if="item.badge"
                      class="absolute end-1 top-1 inline-flex h-4 min-w-[1.125rem] items-center justify-center px-1 text-[9px] leading-none font-bold tabular-nums"
                      :class="badgeClass(item.badgeColor)"
                    >
                      {{ item.badge }}
                    </span>

                    <span
                      v-if="item.children?.length"
                      class="absolute end-1 bottom-1 h-1 w-1 bg-brand-500/70"
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
                class="relative flex h-9 items-center gap-3 overflow-hidden px-3 no-underline transition-colors duration-100"
                :class="
                  isGroupActive(item)
                    ? `bg-[color:var(--sidebar-item-bg-active)] font-semibold text-white`
                    : `text-[#e1dfdd] hover:bg-white/[0.08] hover:text-white`
                "
                @click="(e) => onItemClick(item, e)"
              >
                <span
                  v-if="isGroupActive(item)"
                  class="absolute inset-s-0 top-0 bottom-0 w-[2px] bg-brand-500"
                />
                <component
                  :is="getIcon(item.icon)"
                  class="h-4 w-4 shrink-0"
                  :class="isGroupActive(item) ? 'text-white' : `text-[#c8c6c4]`"
                />
                <span class="flex-1 truncate text-md">{{ item.label }}</span>

                <span
                  v-if="item.badge"
                  class="inline-flex h-4 min-w-[1.125rem] items-center justify-center px-1 text-[9px] leading-none font-bold tabular-nums"
                  :class="badgeClass(item.badgeColor)"
                >
                  {{ item.badge }}
                </span>

                <component
                  :is="isGroupExpanded(groupKey(item)) ? ChevronDown : ChevronRight"
                  v-if="item.children?.length"
                  class="h-3.5 w-3.5 shrink-0 text-[#c8c6c4] rtl:[&:not(.lucide-chevron-down)]:rotate-180"
                />
              </Link>

              <!-- Inline children (expanded mode) -->
              <div
                v-if="item.children?.length && isGroupExpanded(groupKey(item))"
                class="flex flex-col bg-black/15"
              >
                <template v-for="(child, i) in item.children" :key="child.label">
                  <div
                    v-if="child.group && (i === 0 || item.children?.[i - 1]?.group !== child.group)"
                    class="ps-10 pt-2 pb-1 text-[10px] font-semibold tracking-[0.14em] text-[#a19f9d] uppercase select-none"
                  >
                    {{ child.group }}
                  </div>

                  <Link
                    :href="child.url ?? '#'"
                    class="relative flex h-8 items-center gap-2 ps-10 pe-3 text-[12.5px] no-underline transition-colors duration-100"
                    :class="
                      isActive(child)
                        ? `bg-[color:var(--sidebar-item-bg-active)] font-semibold text-white`
                        : `text-[#e1dfdd] hover:bg-white/[0.08] hover:text-white`
                    "
                  >
                    <span
                      v-if="isActive(child)"
                      class="absolute inset-s-0 top-0 bottom-0 w-[2px] bg-brand-500"
                    />
                    <component
                      :is="getIcon(child.icon)"
                      v-if="child.icon"
                      class="h-3.5 w-3.5 shrink-0"
                      :class="isActive(child) ? `text-white` : `text-[#c8c6c4]`"
                    />
                    <span class="flex-1 truncate">{{ child.label }}</span>

                    <span
                      v-if="child.badge"
                      class="inline-flex h-4 min-w-[1.125rem] items-center justify-center px-1 text-[9px] leading-none font-bold tabular-nums"
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
        class="absolute start-full top-0 z-10 flex h-full w-(--flyout-width) flex-col border-s border-paper-200 bg-card shadow-[var(--shadow-blade)]"
      >
        <!-- Flyout header -->
        <div
          class="flex h-(--topbar-height) shrink-0 items-center justify-between border-b border-paper-200 bg-paper-75 px-4"
        >
          <span class="text-[13px] font-semibold tracking-tight text-ink-900">{{
            activeGroup.label
          }}</span>
          <button
            type="button"
            class="text-ink-500 transition-colors hover:text-brand-600 rtl:rotate-180"
            @click="closeFlyout"
          >
            <ChevronLeft class="h-4 w-4" />
          </button>
        </div>

        <!-- Flyout nav items -->
        <nav class="scrollbar-darejer flex flex-1 flex-col overflow-y-auto py-1">
          <template v-for="(child, i) in activeGroup.children" :key="child.label">
            <div
              v-if="
                child.group && (i === 0 || activeGroup.children?.[i - 1]?.group !== child.group)
              "
              class="px-4 pt-3 pb-1 text-[10px] font-semibold tracking-[0.14em] text-ink-500 uppercase select-none"
            >
              {{ child.group }}
            </div>

            <Link
              :href="child.url ?? '#'"
              class="relative flex h-8 items-center gap-2.5 px-4 text-[13px] no-underline transition-colors duration-100"
              :class="
                isActive(child)
                  ? 'bg-brand-50 font-semibold text-brand-700'
                  : `text-ink-700 hover:bg-paper-100 hover:text-ink-900`
              "
              @click="closeFlyout"
            >
              <span
                v-if="isActive(child)"
                class="absolute inset-s-0 top-0 bottom-0 w-[2px] bg-brand-500"
              />
              <component
                :is="getIcon(child.icon)"
                v-if="child.icon"
                class="h-3.5 w-3.5 shrink-0"
                :class="isActive(child) ? 'text-brand-600' : `text-ink-500`"
              />
              <span class="flex-1 truncate">{{ child.label }}</span>

              <span
                v-if="child.badge"
                class="inline-flex h-4 min-w-[1.125rem] items-center justify-center px-1 text-[9px] leading-none font-bold tabular-nums"
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
    <div v-if="flyoutOpen" class="fixed inset-0 z-[9]" @click="closeFlyout" />
  </div>
</template>
