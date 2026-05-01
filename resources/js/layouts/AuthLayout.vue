<script setup lang="ts">
import { computed }                from 'vue'
import { usePage }                 from '@inertiajs/vue3'
import { ConfigProvider }          from 'reka-ui'
import type { DarejerSharedProps } from '@/types/darejer'

const page      = usePage<DarejerSharedProps>()
const locale    = computed(() => page.props.darejer?.locale    ?? 'en')
const direction = computed(() => page.props.darejer?.direction ?? 'ltr')
</script>

<template>
    <ConfigProvider :dir="direction" :locale="locale">
        <div class="min-h-screen flex items-center justify-center bg-paper-50 text-ink-900 antialiased px-6 sm:px-10 py-12 lg:py-16">
            <div class="w-full max-w-sm shadow-md bg-card">
                <slot />
            </div>
        </div>
    </ConfigProvider>
</template>
