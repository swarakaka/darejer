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
  { value: 'light', label: __('Light'), icon: Sun },
  { value: 'dark', label: __('Dark'), icon: Moon },
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
      class="flex h-(--topbar-height) w-10 cursor-pointer items-center justify-center rounded-none text-white transition-colors outline-none hover:bg-white/[0.12]"
      :aria-label="__('Toggle theme')"
    >
      <component :is="triggerIcon" class="h-4 w-4" />
    </DropdownMenuTrigger>
    <DropdownMenuContent align="end" class="w-44">
      <DropdownMenuLabel
        class="px-3 py-1.5 text-[10px] font-semibold tracking-[0.14em] text-ink-500 uppercase"
      >
        {{ __('Appearance') }}
      </DropdownMenuLabel>
      <DropdownMenuItem
        v-for="opt in options"
        :key="opt.value"
        class="flex cursor-pointer items-center gap-2 text-sm"
        :class="mode === opt.value ? 'font-medium text-brand-600' : ''"
        @click="setMode(opt.value)"
      >
        <component :is="opt.icon" class="h-3.5 w-3.5" />
        <span class="flex-1">{{ opt.label }}</span>
        <Check v-if="mode === opt.value" class="h-3.5 w-3.5" />
      </DropdownMenuItem>
    </DropdownMenuContent>
  </DropdownMenu>
</template>
