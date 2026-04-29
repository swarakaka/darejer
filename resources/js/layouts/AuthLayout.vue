<script setup lang="ts">
import { computed }                from 'vue'
import { usePage }                 from '@inertiajs/vue3'
import { ConfigProvider }          from 'reka-ui'
import { ShieldCheck, Sparkles, Workflow } from 'lucide-vue-next'
import useTranslation              from '@/composables/useTranslation'
import type { DarejerSharedProps } from '@/types/darejer'

const page      = usePage<DarejerSharedProps>()
const locale    = computed(() => page.props.darejer?.locale    ?? 'en')
const direction = computed(() => page.props.darejer?.direction ?? 'ltr')

const { __ } = useTranslation()
</script>

<template>
    <ConfigProvider :dir="direction" :locale="locale">
        <div class="min-h-screen flex bg-paper-50 text-ink-900 antialiased">

            <!-- Brand hero panel (hidden on mobile) -->
            <aside
                class="hidden lg:flex flex-col justify-between relative w-2/5 xl:w-1/2 p-10 xl:p-14
                       bg-gradient-to-br from-brand-700 via-brand-800 to-ink-950 text-paper-50 overflow-hidden"
            >
                <div
                    class="pointer-events-none absolute inset-0 opacity-[0.07]
                           bg-[radial-gradient(circle_at_30%_20%,white,transparent_55%)]"
                />
                <div
                    class="pointer-events-none absolute -top-32 -end-32 w-96 h-96 rounded-full
                           bg-brand-500/30 blur-3xl"
                />
                <div
                    class="pointer-events-none absolute -bottom-40 -start-32 w-[28rem] h-[28rem] rounded-full
                           bg-brand-400/20 blur-3xl"
                />

                <div class="relative flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-md bg-white/10 backdrop-blur-sm border border-white/15 flex items-center justify-center">
                        <span class="text-white text-lg font-semibold leading-none">D</span>
                    </div>
                    <span class="text-sm font-semibold tracking-[0.28em] uppercase text-white/90">
                        Darejer
                    </span>
                </div>

                <div class="relative space-y-8 max-w-md">
                    <h2 class="text-3xl xl:text-4xl font-semibold tracking-tight leading-[1.15] text-white">
                        {{ __('The operating system for modern enterprises.') }}
                    </h2>
                    <p class="text-sm xl:text-base text-paper-100/80 leading-relaxed">
                        {{ __('Compose data, workflows, and reporting on a single, governed surface — built to scale with your organization.') }}
                    </p>

                    <ul class="space-y-3 text-sm">
                        <li class="flex items-start gap-3">
                            <span class="shrink-0 mt-0.5 w-7 h-7 rounded-md bg-white/10 border border-white/15 flex items-center justify-center">
                                <Workflow class="w-3.5 h-3.5 text-white" />
                            </span>
                            <span class="text-paper-100/85">{{ __('Configurable screens, actions, and approvals.') }}</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="shrink-0 mt-0.5 w-7 h-7 rounded-md bg-white/10 border border-white/15 flex items-center justify-center">
                                <ShieldCheck class="w-3.5 h-3.5 text-white" />
                            </span>
                            <span class="text-paper-100/85">{{ __('Enterprise-grade governance and audit.') }}</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="shrink-0 mt-0.5 w-7 h-7 rounded-md bg-white/10 border border-white/15 flex items-center justify-center">
                                <Sparkles class="w-3.5 h-3.5 text-white" />
                            </span>
                            <span class="text-paper-100/85">{{ __('Designed for clarity, density, and speed.') }}</span>
                        </li>
                    </ul>
                </div>

                <div class="relative flex items-center justify-between text-2xs uppercase tracking-[0.18em] text-paper-100/60 tabular-nums">
                    <span>{{ __('© :year Darejer', { year: new Date().getFullYear() }) }}</span>
                    <span>{{ __('Secured with TLS') }}</span>
                </div>
            </aside>

            <!-- Form panel -->
            <main class="flex-1 flex items-center justify-center px-6 sm:px-10 py-12 lg:py-16">
                <div class="w-full max-w-sm">
                    <slot />
                </div>
            </main>
        </div>
    </ConfigProvider>
</template>
