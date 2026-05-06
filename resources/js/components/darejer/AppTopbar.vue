<script setup lang="ts">
import { ref } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { LogOut, Bell, Globe, Menu, ChevronDown } from 'lucide-vue-next'
import AppNotifications from '@/components/darejer/AppNotifications.vue'
import AppGlobalSearch from '@/components/darejer/AppGlobalSearch.vue'
import AppThemeToggle from '@/components/darejer/AppThemeToggle.vue'
import { useLanguages } from '@/composables/useLanguages'
import { useAlerts } from '@/composables/useAlerts'
import { useSidebar } from '@/composables/useSidebar'
import useTranslation from '@/composables/useTranslation'
import type { DarejerSharedProps } from '@/types/darejer'

const page = usePage<DarejerSharedProps>()

const { __ } = useTranslation()
const { toggleMobile, toggleCollapsed, isMobile } = useSidebar()

function onMenuClick() {
  if (isMobile.value) {
    toggleMobile()
  } else {
    toggleCollapsed()
  }
}

// `currentLocale` comes from `page.props.darejer.locale` (set server-side
// by the locale middleware). It's reactive via `usePage()`, so the topbar
// dropdown re-renders the new label after every Inertia visit.
const { languages, currentLocale, isMultilingual, localeLabel, localeName } = useLanguages()

// Notifications: bell badge reflects the live unread count maintained by
// the shared `useAlerts` store (Reverb push + initial REST hydration).
const { unreadCount, hasUnread } = useAlerts()
const notificationsOpen = ref(false)

function switchLanguage(locale: string) {
  router.post(
    route('darejer.locale.update').toString(),
    { locale },
    { preserveScroll: true, preserveState: false },
  )
}

const initials = (name: string) =>
  name
    .split(' ')
    .map((n) => n[0])
    .slice(0, 2)
    .join('')
    .toUpperCase()

function logout() {
  router.post(route('logout').toString())
}
</script>

<template>
  <header
    class="flex h-(--topbar-height) shrink-0 items-center gap-0 border-b border-(--topbar-border) bg-(--topbar-bg) text-(--topbar-foreground) print:hidden"
  >
    <!-- Hamburger — collapses/expands the sidebar (or opens the drawer on mobile) -->
    <button
      type="button"
      class="flex h-(--topbar-height) w-11 shrink-0 items-center justify-center rounded-none text-white transition-colors hover:bg-white/[0.12]"
      :aria-label="__('Toggle navigation')"
      @click="onMenuClick"
    >
      <Menu class="h-[18px] w-[18px]" />
    </button>

    <!-- Product wordmark -->
    <span
      class="hidden h-full items-center pe-3 text-[15px] font-normal tracking-tight text-white select-none md:inline-flex"
    >
      {{ __('Darejer') }}
    </span>

    <!-- Global search — sits in the centre column on Azure portal -->
    <div class="flex flex-1 justify-center px-2">
      <AppGlobalSearch />
    </div>

    <!-- Right rail (becomes left rail in RTL via `ms-auto`) -->
    <div class="flex h-full items-center">
      <button
        type="button"
        class="relative flex h-(--topbar-height) w-10 items-center justify-center rounded-none text-white transition-colors hover:bg-white/[0.12]"
        :aria-label="__('Notifications')"
        @click="notificationsOpen = true"
      >
        <Bell class="h-4 w-4" />
        <span
          v-if="hasUnread"
          class="absolute end-1.5 top-1.5 inline-flex h-4 min-w-[1rem] items-center justify-center bg-danger-500 px-1 text-[9px] leading-none font-semibold text-white tabular-nums ring-2 ring-(--topbar-bg)"
        >
          {{ unreadCount > 99 ? '99+' : unreadCount }}
        </span>
      </button>

      <AppNotifications v-model:open="notificationsOpen" />

      <!-- Theme switcher: light / dark / system -->
      <AppThemeToggle />

      <!-- Language switcher (only when multiple languages configured) -->
      <DropdownMenu v-if="isMultilingual">
        <DropdownMenuTrigger
          class="flex h-(--topbar-height) items-center gap-1.5 rounded-none px-3 text-xs font-semibold text-white tabular-nums transition-colors hover:bg-white/[0.12]"
        >
          <Globe class="h-3.5 w-3.5" />
          <span class="tracking-wider uppercase">{{ localeLabel(currentLocale) }}</span>
          <ChevronDown class="h-3 w-3 text-white/70" />
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-44">
          <DropdownMenuLabel
            class="px-3 py-1.5 text-[10px] font-semibold tracking-[0.14em] text-ink-500 uppercase"
          >
            {{ __('Language') }}
          </DropdownMenuLabel>
          <DropdownMenuItem
            v-for="locale in languages"
            :key="locale"
            class="flex cursor-pointer items-center gap-2 text-sm"
            :class="currentLocale === locale ? `font-medium text-brand-600` : ''"
            @click="switchLanguage(locale)"
          >
            <span
              class="inline-flex h-4 w-7 items-center justify-center border border-paper-200 bg-paper-100 text-[9px] font-bold tracking-wide text-ink-700 uppercase"
            >
              {{ localeLabel(locale) }}
            </span>
            <span class="flex-1">{{ localeName(locale) }}</span>
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>

      <DropdownMenu v-if="page.props.auth.user">
        <DropdownMenuTrigger
          class="flex h-(--topbar-height) cursor-pointer items-center gap-2.5 rounded-none ps-3 pe-3 text-white transition-colors outline-none hover:bg-white/[0.12]"
        >
          <div class="hidden flex-col items-end leading-tight md:flex">
            <span class="text-[12px] font-semibold text-white">{{
              page.props.auth.user.username
            }}</span>
            <span class="text-[10px] font-medium tracking-[0.08em] text-white/80 uppercase">{{
              __('Admin')
            }}</span>
          </div>
          <Avatar class="h-7 w-7 rounded-full">
            <AvatarFallback
              class="bg-paper-50 text-[11px] font-semibold tracking-wider text-brand-700 tabular-nums"
            >
              {{ initials(page.props.auth.user.username) }}
            </AvatarFallback>
          </Avatar>
        </DropdownMenuTrigger>

        <DropdownMenuContent align="end" class="w-60">
          <DropdownMenuLabel class="px-3 py-2.5 font-normal">
            <p class="text-sm font-semibold text-ink-900">{{ page.props.auth.user.username }}</p>
            <p class="mt-0.5 text-xs break-all text-ink-500 tabular-nums">
              {{ page.props.auth.user.email }}
            </p>
          </DropdownMenuLabel>
          <DropdownMenuSeparator />
          <DropdownMenuItem class="flex cursor-pointer items-center gap-2 text-sm" @click="logout">
            <LogOut class="h-3.5 w-3.5" />
            {{ __('Sign out') }}
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
    </div>
  </header>
</template>
