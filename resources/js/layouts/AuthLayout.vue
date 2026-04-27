<script setup lang="ts">
import { computed }                from 'vue'
import { usePage }                 from '@inertiajs/vue3'
import { ConfigProvider }          from 'reka-ui'
import type { DarejerSharedProps } from '@/types/darejer'
import useTranslation              from '@/composables/useTranslation'

const page      = usePage<DarejerSharedProps>()
const locale    = computed(() => page.props.darejer?.locale    ?? 'en')
const direction = computed(() => page.props.darejer?.direction ?? 'ltr')

const { __ } = useTranslation()
</script>

<template>
    <ConfigProvider :dir="direction" :locale="locale">
    <div class="min-h-screen grid grid-cols-1 lg:grid-cols-[1.15fr_1fr] bg-paper-50">

        <!-- Left: editorial brand pane -->
        <div class="hidden lg:flex flex-col justify-between p-14 bg-ink-900 text-paper-50 relative overflow-hidden">

            <!-- decorative rule motif -->
            <div
                aria-hidden="true"
                class="absolute inset-0 opacity-[0.06]"
                style="background-image: linear-gradient(to right, #ffffff 1px, transparent 1px);
                       background-size: 68px 100%;"
            />

            <div class="relative flex items-center gap-3">
                <div class="w-6 h-6 rounded-sm bg-brand-500 flex items-center justify-center">
                    <span class="font-serif text-paper-50 text-base leading-none translate-y-[1px]">D</span>
                </div>
                <span class="text-sm font-medium tracking-[0.24em] uppercase text-paper-100">Darejer</span>
            </div>

            <div class="relative">
                <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-brand-300 mb-5">{{ __('No. 01 · The Ledger') }}</p>
                <h1 class="font-serif text-paper-50 leading-[1.05] tracking-tight text-5xl mb-6">
                    {{ __('Write the') }}<br />
                    <em class="italic text-brand-300">{{ __('business') }}</em>,<br />
                    {{ __('not the interface.') }}
                </h1>
                <p class="text-sm text-paper-200/80 leading-relaxed max-w-sm">
                    {{ __('Darejer is a Laravel platform that renders every screen from PHP. Forms, tables, actions, permissions — all declared in your controllers, reconciled on every request.') }}
                </p>
            </div>

            <div class="relative flex items-center gap-3 text-xs text-paper-300 tabular-nums">
                <span class="tracking-widest">v0.1</span>
                <span class="text-ink-500">·</span>
                <span>Laravel 13 · Vue 3 · Inertia v3</span>
            </div>
        </div>

        <!-- Right: form pane -->
        <div class="flex flex-col items-center justify-center px-8 py-16 bg-paper-50">

            <div class="lg:hidden flex items-center gap-2.5 mb-12">
                <div class="w-6 h-6 rounded-sm bg-brand-600 flex items-center justify-center">
                    <span class="font-serif text-white text-base leading-none translate-y-[1px]">D</span>
                </div>
                <span class="text-sm font-medium tracking-[0.24em] uppercase text-ink-700">Darejer</span>
            </div>

            <div class="w-full max-w-[22rem]">
                <slot />
            </div>
        </div>

    </div>
    </ConfigProvider>
</template>
