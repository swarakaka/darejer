<script setup lang="ts">
import { usePage } from '@inertiajs/vue3'
import { CheckCircle, ArrowRight, Sparkles } from 'lucide-vue-next'
import type { DarejerSharedProps }      from '@/types/darejer'
import useTranslation                   from '@/composables/useTranslation'

const page = usePage<DarejerSharedProps>()

const { __ } = useTranslation()

const checks = [
    { label: 'Vue 3' },
    { label: 'Inertia v3' },
    { label: 'Tailwind CSS v4' },
    { label: 'shadcn-vue' },
    { label: 'Lucide Icons' },
    { label: 'TypeScript' },
]
</script>

<template>
    <div class="min-h-screen bg-paper-50 text-ink-900 antialiased">
        <!-- Subtle decorative background -->
        <div class="pointer-events-none fixed inset-0 overflow-hidden">
            <div class="absolute -top-40 start-1/2 -translate-x-1/2 w-[60rem] h-[60rem] rounded-full bg-brand-100/40 blur-3xl" />
            <div class="absolute bottom-0 end-0 w-96 h-96 rounded-full bg-brand-50 blur-3xl" />
        </div>

        <main class="relative max-w-3xl mx-auto px-6 py-16 sm:py-24">

            <!-- Brand -->
            <div class="flex items-center gap-2.5 mb-10">
                <div class="w-8 h-8 rounded-md bg-brand-600 flex items-center justify-center ring-1 ring-brand-400/30 shadow-sm">
                    <span class="text-white font-semibold text-sm leading-none">D</span>
                </div>
                <span class="text-xs font-semibold tracking-[0.28em] uppercase text-ink-700">Darejer</span>
            </div>

            <!-- Eyebrow -->
            <div class="inline-flex items-center gap-1.5 mb-5 px-2.5 py-1 rounded-full bg-white border border-paper-200 shadow-xs">
                <Sparkles class="w-3 h-3 text-brand-600" />
                <span class="text-2xs font-semibold uppercase tracking-[0.16em] text-ink-600">{{ __('Playground') }}</span>
            </div>

            <!-- Hero -->
            <h1 class="text-4xl sm:text-5xl font-semibold tracking-tight leading-[1.1] text-ink-900">
                {{ __('Frontend stack') }}<br />
                <span class="text-brand-700">{{ __('is operational.') }}</span>
            </h1>
            <p class="mt-5 max-w-xl text-base text-ink-500 leading-relaxed">
                {{ __('All systems ready. Below is the live state of your runtime, dependencies, and shared Inertia props from the Darejer service provider.') }}
            </p>

            <!-- Stack -->
            <section class="mt-10">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-[10px] font-semibold uppercase tracking-[0.18em] text-ink-500">
                        {{ __('Runtime') }}
                    </h2>
                    <span class="text-2xs uppercase tracking-[0.14em] text-success-600 font-semibold">
                        {{ __('All ready') }}
                    </span>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                    <div
                        v-for="check in checks"
                        :key="check.label"
                        class="flex items-center justify-between gap-2 px-3 py-2.5 rounded-md bg-white border border-paper-200 shadow-xs"
                    >
                        <span class="text-sm font-medium text-ink-800 truncate">{{ check.label }}</span>
                        <CheckCircle class="w-4 h-4 text-success-600 shrink-0" />
                    </div>
                </div>
            </section>

            <!-- Shared props -->
            <section class="mt-8 rounded-lg border border-paper-200 bg-white shadow-xs p-5">
                <h3 class="text-[10px] font-semibold uppercase tracking-[0.18em] text-ink-500 mb-3">
                    {{ __('Inertia shared props') }}
                </h3>
                <dl class="grid grid-cols-[8rem_1fr] gap-x-3 gap-y-2.5 text-sm">
                    <dt class="text-ink-500">{{ __('Languages') }}</dt>
                    <dd class="flex flex-wrap gap-1.5">
                        <span
                            v-for="lang in page.props.darejer.languages"
                            :key="lang"
                            class="inline-flex items-center px-2 py-0.5 rounded-sm text-2xs font-semibold uppercase tracking-wide bg-paper-100 text-ink-700 border border-paper-200"
                        >
                            {{ lang }}
                        </span>
                    </dd>
                    <dt class="text-ink-500">{{ __('Default') }}</dt>
                    <dd>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-sm text-2xs font-semibold uppercase tracking-wide bg-brand-50 text-brand-700 border border-brand-100">
                            {{ page.props.darejer.default_language }}
                        </span>
                    </dd>
                </dl>
            </section>

            <!-- CTA -->
            <div class="mt-10">
                <button
                    type="button"
                    class="group inline-flex items-center justify-center gap-2 h-11 px-5 rounded-md
                           bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold
                           shadow-sm transition-colors"
                >
                    {{ __('Continue to App') }}
                    <ArrowRight class="w-4 h-4 transition-transform group-hover:translate-x-0.5 rtl:rotate-180 rtl:group-hover:-translate-x-0.5" />
                </button>
            </div>

        </main>
    </div>
</template>
