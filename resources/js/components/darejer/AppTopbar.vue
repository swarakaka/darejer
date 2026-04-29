<script setup lang="ts">
import { ref }                     from 'vue'
import { usePage, router }         from '@inertiajs/vue3'
import { Avatar, AvatarFallback }  from '@/components/ui/avatar'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { LogOut, Bell, Globe, Menu, ChevronDown } from 'lucide-vue-next'
import AppNotifications            from '@/components/darejer/AppNotifications.vue'
import AppGlobalSearch             from '@/components/darejer/AppGlobalSearch.vue'
import { useLanguages }            from '@/composables/useLanguages'
import { useAlerts }               from '@/composables/useAlerts'
import { useSidebar }              from '@/composables/useSidebar'
import useTranslation              from '@/composables/useTranslation'
import type { DarejerSharedProps } from '@/types/darejer'

const page = usePage<DarejerSharedProps>()

const { __ } = useTranslation()
const { toggleMobile } = useSidebar()

// `currentLocale` comes from `page.props.darejer.locale` (set server-side
// by the locale middleware). It's reactive via `usePage()`, so the topbar
// dropdown re-renders the new label after every Inertia visit.
const { languages, currentLocale, isMultilingual, localeLabel, localeName } = useLanguages()

// Notifications: bell badge reflects the live unread count maintained by
// the shared `useAlerts` store (Reverb push + initial REST hydration).
const { unreadCount, hasUnread } = useAlerts()
const notificationsOpen = ref(false)

function switchLanguage(locale: string) {
    router.get(
        window.location.pathname,
        { lang: locale },
        { preserveState: false, preserveScroll: false }
    )
}

const initials = (name: string) =>
    name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase()

function logout() {
    router.post(route('logout').toString())
}
</script>

<template>
    <header
        class="flex items-center h-(--topbar-height) shrink-0 gap-0
               bg-(--topbar-bg) text-(--topbar-foreground) border-b border-(--topbar-border)"
    >
        <!-- Hamburger / mobile menu toggle — always visible like the Azure portal -->
        <button
            type="button"
            class="flex items-center justify-center w-11 h-(--topbar-height) rounded-none text-white hover:bg-white/[0.12] transition-colors shrink-0"
            :aria-label="__('Show portal menu')"
            @click="toggleMobile"
        >
            <Menu class="w-[18px] h-[18px]" />
        </button>

        <!-- Product wordmark — Azure portal "Microsoft Azure" placement -->
        <span class="hidden md:inline-flex items-center h-full pe-3 text-[15px] font-normal tracking-tight text-white select-none">
            {{ __('Microsoft Darejer') }}
        </span>

        <!-- Global search — sits in the centre column on Azure portal -->
        <div class="flex-1 flex justify-center px-2">
            <AppGlobalSearch />
        </div>

        <!-- Right rail (becomes left rail in RTL via `ms-auto`) -->
        <div class="flex items-center h-full">

            <button
                type="button"
                class="relative flex items-center justify-center w-10 h-(--topbar-height) rounded-none transition-colors text-white hover:bg-white/[0.12]"
                :aria-label="__('Notifications')"
                @click="notificationsOpen = true"
            >
                <Bell class="w-4 h-4" />
                <span
                    v-if="hasUnread"
                    class="absolute top-1.5 end-1.5 inline-flex items-center justify-center
                           min-w-[1rem] h-4 px-1 bg-danger-500 text-white
                           text-[9px] font-semibold tabular-nums leading-none ring-2 ring-(--topbar-bg)"
                >
                    {{ unreadCount > 99 ? '99+' : unreadCount }}
                </span>
            </button>

            <AppNotifications v-model:open="notificationsOpen" />

            <!-- Language switcher (only when multiple languages configured) -->
            <DropdownMenu v-if="isMultilingual">
                <DropdownMenuTrigger
                    class="flex items-center gap-1.5 h-(--topbar-height) px-3 rounded-none text-white
                           hover:bg-white/[0.12] transition-colors text-xs font-semibold tabular-nums"
                >
                    <Globe class="w-3.5 h-3.5" />
                    <span class="uppercase tracking-wider">{{ localeLabel(currentLocale) }}</span>
                    <ChevronDown class="w-3 h-3 text-white/70" />
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-44">
                    <DropdownMenuLabel class="text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-500 px-3 py-1.5">
                        {{ __('Language') }}
                    </DropdownMenuLabel>
                    <DropdownMenuItem
                        v-for="locale in languages"
                        :key="locale"
                        class="flex items-center gap-2 text-sm cursor-pointer"
                        :class="currentLocale === locale ? 'text-brand-600 font-medium' : ''"
                        @click="switchLanguage(locale)"
                    >
                        <span
                            class="inline-flex items-center justify-center w-7 h-4
                                   bg-paper-100 border border-paper-200 text-[9px] font-bold tracking-wide text-ink-700 uppercase"
                        >
                            {{ localeLabel(locale) }}
                        </span>
                        <span class="flex-1">{{ localeName(locale) }}</span>
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>

            <DropdownMenu v-if="page.props.auth.user">
                <DropdownMenuTrigger
                    class="flex items-center gap-2.5 h-(--topbar-height) ps-3 pe-3 rounded-none outline-none cursor-pointer
                           text-white hover:bg-white/[0.12] transition-colors"
                >
                    <div class="hidden md:flex flex-col items-end leading-tight">
                        <span class="text-[12px] font-semibold text-white">{{ page.props.auth.user.username }}</span>
                        <span class="text-[10px] font-medium uppercase tracking-[0.08em] text-white/80">{{ __('Admin') }}</span>
                    </div>
                    <Avatar class="w-7 h-7 rounded-full">
                        <AvatarFallback
                            class="bg-paper-50 text-brand-700 text-[11px] font-semibold tracking-wider tabular-nums"
                        >
                            {{ initials(page.props.auth.user.username) }}
                        </AvatarFallback>
                    </Avatar>
                </DropdownMenuTrigger>

                <DropdownMenuContent align="end" class="w-60">
                    <DropdownMenuLabel class="font-normal px-3 py-2.5">
                        <p class="text-sm font-semibold text-ink-900">{{ page.props.auth.user.username }}</p>
                        <p class="text-xs text-ink-500 mt-0.5 tabular-nums break-all">{{ page.props.auth.user.email }}</p>
                    </DropdownMenuLabel>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem
                        class="flex items-center gap-2 cursor-pointer text-sm"
                        @click="logout"
                    >
                        <LogOut class="w-3.5 h-3.5" />
                        {{ __('Sign out') }}
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    </header>
</template>
