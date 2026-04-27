<script setup lang="ts">
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
import { LogOut, Search, Bell, Command, Globe } from 'lucide-vue-next'
import { useLanguages }            from '@/composables/useLanguages'
import useTranslation              from '@/composables/useTranslation'
import type { DarejerSharedProps } from '@/types/darejer'

const page = usePage<DarejerSharedProps>()

const { __ } = useTranslation()

// `currentLocale` comes from `page.props.darejer.locale` (set server-side
// by the locale middleware). It's reactive via `usePage()`, so the topbar
// dropdown re-renders the new label after every Inertia visit. The previous
// implementation read `window.location.search` which is NOT reactive — the
// label only updated on a full reload.
const { languages, currentLocale, isMultilingual, localeLabel, localeName } = useLanguages()

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
        class="flex items-center bg-[color:var(--topbar-bg)] border-b border-[color:var(--topbar-border)]
               shrink-0 px-4 gap-4"
        :style="{ height: 'var(--topbar-height)' }"
    >
        <!-- Wordmark -->
        <div class="flex items-baseline gap-2 shrink-0 select-none">
            <span class="font-serif text-lg leading-none text-ink-900 tracking-tight">Darejer</span>
            <span class="text-[10px] font-semibold uppercase tracking-[0.18em] text-ink-300 tabular-nums">v0.1</span>
        </div>

        <span class="h-4 w-px bg-paper-300 shrink-0" />

        <!-- Search -->
        <div class="flex-1 max-w-md">
            <div class="relative group">
                <Search
                    class="absolute start-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5
                           text-ink-400 group-focus-within:text-brand-600 transition-colors"
                />
                <input
                    type="search"
                    :placeholder="__('Search records, screens, or run a command…')"
                    class="w-full h-8 ps-8 pe-14 text-sm bg-paper-100/70 border-none rounded-sm
                           placeholder:text-ink-400 focus:outline-none focus:bg-white
                           focus:ring-1 focus:ring-brand-500/50 transition-all"
                />
                <kbd
                    class="absolute end-2 top-1/2 -translate-y-1/2 hidden md:flex items-center gap-1
                           text-[10px] font-medium text-ink-400 bg-paper-200/70
                           border border-paper-300 rounded-sm px-1 h-[18px] tabular-nums"
                >
                    <Command class="w-2.5 h-2.5" />K
                </kbd>
            </div>
        </div>

        <!-- Right rail (becomes left rail in RTL via `ms-auto`) -->
        <div class="flex items-center gap-1 ms-auto">

            <button
                type="button"
                class="relative flex items-center justify-center w-8 h-8 rounded-sm text-ink-500
                       hover:text-ink-800 hover:bg-paper-100 transition-colors"
                :aria-label="__('Notifications')"
            >
                <Bell class="w-4 h-4" />
                <span class="absolute top-1.5 end-1.5 w-1.5 h-1.5 rounded-full bg-danger-500" />
            </button>

            <!-- Language switcher (only when multiple languages configured) -->
            <DropdownMenu v-if="isMultilingual">
                <DropdownMenuTrigger
                    class="flex items-center gap-1 h-7 px-2 rounded-sm text-ink-500
                           hover:bg-paper-100 hover:text-ink-800 transition-colors text-xs font-medium tabular-nums"
                >
                    <Globe class="w-3.5 h-3.5" />
                    {{ localeLabel(currentLocale) }}
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-40">
                    <DropdownMenuItem
                        v-for="locale in languages"
                        :key="locale"
                        class="flex items-center gap-2 text-sm cursor-pointer"
                        :class="currentLocale === locale ? 'text-brand-600 font-medium' : ''"
                        @click="switchLanguage(locale)"
                    >
                        <span
                            class="inline-flex items-center justify-center w-7 h-4 rounded-sm
                                   bg-paper-100 border border-paper-200 text-[9px] font-bold tracking-wide text-ink-600"
                        >
                            {{ localeLabel(locale) }}
                        </span>
                        <span class="flex-1">{{ localeName(locale) }}</span>
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>

            <span class="h-4 w-px bg-paper-300 mx-1.5" />

            <DropdownMenu v-if="page.props.auth.user">
                <DropdownMenuTrigger
                    class="flex items-center gap-2 ps-1 pe-2 py-1 rounded-sm outline-none cursor-pointer
                           hover:bg-paper-100 transition-colors"
                >
                    <Avatar class="w-7 h-7">
                        <AvatarFallback
                            class="bg-brand-600 text-white text-[10px] font-semibold tracking-wider tabular-nums"
                        >
                            {{ initials(page.props.auth.user.name) }}
                        </AvatarFallback>
                    </Avatar>
                    <div class="hidden md:flex flex-col items-start leading-tight">
                        <span class="text-xs font-medium text-ink-800">{{ page.props.auth.user.name }}</span>
                        <span class="text-[10px] font-semibold uppercase tracking-[0.18em] text-ink-400">{{ __('Admin') }}</span>
                    </div>
                </DropdownMenuTrigger>

                <DropdownMenuContent align="end" class="w-56">
                    <DropdownMenuLabel class="font-normal px-3 py-2">
                        <p class="text-sm font-medium text-ink-800">{{ page.props.auth.user.name }}</p>
                        <p class="text-xs text-ink-400 mt-0.5 tabular-nums">{{ page.props.auth.user.email }}</p>
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
