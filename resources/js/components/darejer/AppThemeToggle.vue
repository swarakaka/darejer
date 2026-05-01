<script setup lang="ts">
import { computed } from 'vue'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Sun, Moon, Monitor, Check } from 'lucide-vue-next'
import { useTheme, type ThemeMode } from '@/composables/useTheme'
import useTranslation from '@/composables/useTranslation'

const { __ } = useTranslation()
const { mode, isDark, setMode } = useTheme()

const options: Array<{ value: ThemeMode; label: string; icon: typeof Sun }> = [
    { value: 'light',  label: __('Light'),  icon: Sun },
    { value: 'dark',   label: __('Dark'),   icon: Moon },
    { value: 'system', label: __('System'), icon: Monitor },
]

const triggerIcon = computed(() => {
    if (mode.value === 'system') return Monitor
    return isDark.value ? Moon : Sun
})
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger
            class="flex items-center justify-center w-10 h-(--topbar-height) rounded-none transition-colors text-white hover:bg-white/[0.12] outline-none cursor-pointer"
            :aria-label="__('Toggle theme')"
        >
            <component :is="triggerIcon" class="w-4 h-4" />
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-44">
            <DropdownMenuLabel
                class="text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-500 px-3 py-1.5"
            >
                {{ __('Appearance') }}
            </DropdownMenuLabel>
            <DropdownMenuItem
                v-for="opt in options"
                :key="opt.value"
                class="flex items-center gap-2 text-sm cursor-pointer"
                :class="mode === opt.value ? 'text-brand-600 font-medium' : ''"
                @click="setMode(opt.value)"
            >
                <component :is="opt.icon" class="w-3.5 h-3.5" />
                <span class="flex-1">{{ opt.label }}</span>
                <Check v-if="mode === opt.value" class="w-3.5 h-3.5" />
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
