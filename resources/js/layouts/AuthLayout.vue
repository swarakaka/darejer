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
        <div class="min-h-screen flex items-center justify-center px-8 py-16 bg-paper-50">
            <div class="w-full max-w-90">
                <slot />
            </div>
        </div>
    </ConfigProvider>
</template>
