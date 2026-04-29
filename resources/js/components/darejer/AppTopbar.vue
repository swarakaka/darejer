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
        class="flex items-center h-(--topbar-height) shrink-0 px-3 sm:px-5 gap-2 sm:gap-3
               bg-(--topbar-bg) border-b border-(--topbar-border)
               supports-[backdrop-filter]:backdrop-blur-md"
    >
        <!-- Mobile menu toggle -->
        <button
            type="button"
            class="md:hidden flex items-center justify-center w-8 h-8 rounded-md text-ink-500 hover:text-ink-800 hover:bg-paper-100 transition-colors shrink-0"
            :aria-label="__('Open menu')"
            @click="toggleMobile"
        >
            <Menu class="w-4 h-4" />
        </button>

        <!-- Global search -->
        <AppGlobalSearch />

        <!-- Right rail (becomes left rail in RTL via `ms-auto`) -->
        <div class="flex items-center gap-0.5 ms-auto">

            <button
                type="button"
                class="relative flex items-center justify-center w-9 h-9 rounded-md transition-colors"
                :class="hasUnread
                    ? 'text-brand-600 hover:text-brand-700 hover:bg-brand-50'
                    : 'text-ink-500 hover:text-ink-800 hover:bg-paper-100'"
                :aria-label="__('Notifications')"
                @click="notificationsOpen = true"
            >
                <Bell class="w-4 h-4" />
                <span
                    v-if="hasUnread"
                    class="absolute top-1 end-1 inline-flex items-center justify-center
                           min-w-[1rem] h-4 px-1 rounded-full bg-danger-500 text-white
                           text-[9px] font-semibold tabular-nums leading-none ring-2 ring-(--topbar-bg)"
                >
                    {{ unreadCount > 99 ? '99+' : unreadCount }}
                </span>
            </button>

            <AppNotifications v-model:open="notificationsOpen" />

            <!-- Language switcher (only when multiple languages configured) -->
            <DropdownMenu v-if="isMultilingual">
                <DropdownMenuTrigger
                    class="flex items-center gap-1.5 h-8 px-2 rounded-md text-ink-500
                           hover:bg-paper-100 hover:text-ink-800 transition-colors text-xs font-medium tabular-nums"
                >
                    <Globe class="w-3.5 h-3.5" />
                    <span class="uppercase tracking-wider">{{ localeLabel(currentLocale) }}</span>
                    <ChevronDown class="w-3 h-3 text-ink-400" />
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-44">
                    <DropdownMenuLabel class="text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-400 px-3 py-1.5">
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
                            class="inline-flex items-center justify-center w-7 h-4 rounded-sm
                                   bg-paper-100 border border-paper-200 text-[9px] font-bold tracking-wide text-ink-600 uppercase"
                        >
                            {{ localeLabel(locale) }}
                        </span>
                        <span class="flex-1">{{ localeName(locale) }}</span>
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>

            <span class="h-5 w-px bg-paper-300 mx-1.5" />

            <DropdownMenu v-if="page.props.auth.user">
                <DropdownMenuTrigger
                    class="flex items-center gap-2 ps-1 pe-2 py-1 rounded-md outline-none cursor-pointer
                           hover:bg-paper-100 transition-colors"
                >
                    <Avatar class="w-7 h-7 ring-1 ring-paper-200">
                        <AvatarFallback
                            class="bg-brand-600 text-white text-[10px] font-semibold tracking-wider tabular-nums"
                        >
                            {{ initials(page.props.auth.user.username) }}
                        </AvatarFallback>
                    </Avatar>
                    <div class="hidden md:flex flex-col items-start leading-tight">
                        <span class="text-xs font-semibold text-ink-800">{{ page.props.auth.user.username }}</span>
                        <span class="text-[10px] font-medium uppercase tracking-[0.16em] text-ink-400">{{ __('Admin') }}</span>
                    </div>
                    <ChevronDown class="hidden md:block w-3 h-3 text-ink-400" />
                </DropdownMenuTrigger>

                <DropdownMenuContent align="end" class="w-60">
                    <DropdownMenuLabel class="font-normal px-3 py-2.5">
                        <p class="text-sm font-semibold text-ink-800">{{ page.props.auth.user.username }}</p>
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
