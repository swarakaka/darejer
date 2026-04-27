<script setup lang="ts">
import { computed }  from 'vue'
import { Link }      from '@inertiajs/vue3'
import type { DarejerComponent } from '@/types/darejer'

const props = defineProps<{
    component: DarejerComponent
    record:    Record<string, unknown>
    errors:    Record<string, string>
    formData?: Record<string, unknown>
}>()

interface NavItemDef {
    label:   string
    url?:    string
    icon?:   string
    active?: boolean
}

const items   = computed((): NavItemDef[] => (props.component.navItems as NavItemDef[]) ?? [])
const style   = computed(() => (props.component.style as string) ?? 'tabs')
const stretch = computed(() => !!props.component.stretch)

const containerClass = computed(() => {
    if (style.value === 'tabs')      return 'flex border-b border-paper-200'
    if (style.value === 'pills')     return 'flex gap-1 p-1 bg-paper-75 rounded-md border border-paper-200'
    return 'flex gap-0 border-b border-paper-200'   // underline
})

function itemActive(item: NavItemDef): boolean {
    if (item.active) return true
    if (!item.url || typeof window === 'undefined') return false
    try {
        return window.location.pathname === new URL(item.url, window.location.origin).pathname
    } catch {
        return false
    }
}

function itemClass(item: NavItemDef): string {
    const isActive = itemActive(item)

    if (style.value === 'tabs') {
        return [
            'flex items-center gap-1.5 px-4 py-2.5 text-sm font-medium border-b-2 -mb-px',
            'transition-colors duration-100 whitespace-nowrap no-underline',
            stretch.value ? 'flex-1 justify-center' : '',
            isActive
                ? 'border-brand-600 text-brand-700'
                : 'border-transparent text-ink-500 hover:text-ink-800 hover:border-paper-300',
        ].join(' ')
    }

    if (style.value === 'pills') {
        return [
            'flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-sm',
            'transition-colors duration-100 whitespace-nowrap no-underline',
            stretch.value ? 'flex-1 justify-center' : '',
            isActive
                ? 'bg-white text-ink-800 border border-paper-300'
                : 'text-ink-500 hover:text-ink-800 hover:bg-paper-100 border border-transparent',
        ].join(' ')
    }

    // underline
    return [
        'flex items-center gap-1.5 px-3 py-2 text-sm font-medium',
        'border-b-2 -mb-px transition-colors duration-100 whitespace-nowrap no-underline',
        stretch.value ? 'flex-1 justify-center' : '',
        isActive
            ? 'border-brand-600 text-brand-700'
            : 'border-transparent text-ink-500 hover:text-ink-700',
    ].join(' ')
}
</script>

<template>
    <div :class="containerClass" class="col-span-full">
        <template v-for="item in items" :key="item.label">
            <Link
                v-if="item.url"
                :href="item.url"
                :class="itemClass(item)"
            >
                {{ item.label }}
            </Link>
            <button
                v-else
                type="button"
                :class="itemClass(item)"
            >
                {{ item.label }}
            </button>
        </template>
    </div>
</template>
