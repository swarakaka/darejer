<script setup lang="ts">
import { computed } from 'vue'
import { usePage, Link } from '@inertiajs/vue3'
import HomeLayout from '@/layouts/HomeLayout.vue'
import {
  Archive,
  BarChart2,
  BarChart3,
  Bell,
  BookOpen,
  Building,
  Building2,
  Calculator,
  Calendar,
  ClipboardCheck,
  CreditCard,
  Database,
  FileText,
  Folders,
  Globe,
  HelpCircle,
  Inbox,
  Landmark,
  Layers,
  LayoutDashboard,
  LifeBuoy,
  Package,
  Search,
  Settings,
  Shield,
  ShoppingCart,
  Star,
  Tag,
  Truck,
  UserSquare,
  Users,
  Wrench,
} from 'lucide-vue-next'
import useTranslation from '@/composables/useTranslation'
import type { DarejerSharedProps, NavItem } from '@/types/darejer'

defineOptions({ layout: HomeLayout })

const { __ } = useTranslation()

const page = usePage<DarejerSharedProps>()

const iconMap: Record<string, unknown> = {
  Archive,
  BarChart2,
  BarChart3,
  Bell,
  BookOpen,
  Building,
  Building2,
  Calculator,
  Calendar,
  ClipboardCheck,
  CreditCard,
  Database,
  FileText,
  Folders,
  Globe,
  HelpCircle,
  Inbox,
  Landmark,
  Layers,
  LayoutDashboard,
  LifeBuoy,
  Package,
  Search,
  Settings,
  Shield,
  ShoppingCart,
  Star,
  Tag,
  Truck,
  UserSquare,
  Users,
  Wrench,
}

function getIcon(name?: string) {
  return name ? (iconMap[name] ?? LayoutDashboard) : LayoutDashboard
}

// Top-level navigation items become tiles. Empty groups are already
// filtered server-side by NavigationManager::toArray() based on the
// user's permissions, so whatever lands here is what the user can act on.
const tiles = computed((): NavItem[] => page.props.navigation ?? [])

// A group's tile resolves to its own url if set, otherwise the first
// child url — matching ERPNext's behaviour where clicking a module tile
// lands you on its index page.
function tileHref(item: NavItem): string {
  if (item.url) return item.url
  const firstChildUrl = item.children?.find((c) => c.url)?.url
  return firstChildUrl ?? '#'
}

function childCount(item: NavItem): number {
  return item.children?.length ?? 0
}
</script>

<template>
  <div class="flex h-full flex-col overflow-y-auto">
    <div class="mx-auto w-full max-w-3xl px-6 py-10">
      <div
        v-if="tiles.length"
        class="grid grid-cols-2 gap-x-6 gap-y-8 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5"
      >
        <Link
          v-for="item in tiles"
          :key="item.label"
          :href="tileHref(item)"
          class="group flex flex-col items-start gap-2 no-underline outline-none"
        >
          <span
            class="inline-flex h-14 w-14 items-center justify-center rounded-md bg-brand-500 text-white shadow-[0_1px_2px_rgba(0,0,0,0.08)] ring-1 ring-brand-600/30 ring-inset transition-all duration-150 group-hover:-translate-y-0.5 group-hover:bg-brand-600 group-hover:shadow-[0_6px_14px_-6px_rgba(0,120,212,0.55)]"
          >
            <component :is="getIcon(item.icon)" class="h-6 w-6" />
          </span>
          <div class="flex flex-col">
            <span
              class="text-[13px] font-semibold tracking-tight text-ink-900 transition-colors group-hover:text-brand-700"
            >
              {{ item.label }}
            </span>
            <span v-if="childCount(item)" class="text-[11px] text-ink-500 tabular-nums">
              {{ childCount(item) }} {{ childCount(item) === 1 ? __('item') : __('items') }}
            </span>
          </div>
        </Link>
      </div>

      <!-- Empty state — usually only happens for accounts with zero
           permissions; existing nav scaffolding never returns an empty
           list for the admin role. -->
      <div
        v-else
        class="flex flex-col items-center justify-center gap-3 rounded-md border border-dashed border-paper-300 bg-card px-6 py-20 text-center"
      >
        <span
          class="inline-flex h-12 w-12 items-center justify-center rounded-md bg-paper-100 text-ink-400 ring-1 ring-paper-200"
        >
          <LayoutDashboard class="h-5 w-5" />
        </span>
        <p class="text-[13px] font-semibold text-ink-700">
          {{ __('Nothing to show here yet') }}
        </p>
        <p class="max-w-sm text-[12px] text-ink-500">
          {{ __('Ask an administrator to grant you access to a module.') }}
        </p>
      </div>
    </div>
  </div>
</template>
