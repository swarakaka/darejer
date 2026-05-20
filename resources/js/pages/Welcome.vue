<script setup lang="ts">
import { usePage } from '@inertiajs/vue3'
import { CheckCircle, ArrowRight, Sparkles } from 'lucide-vue-next'
import useTranslation from '@/composables/useTranslation'
import type { DarejerSharedProps } from '@/types/darejer'

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
  <div class="bg-paper-50 text-ink-900 min-h-screen antialiased">
    <!-- Subtle decorative background -->
    <div class="pointer-events-none fixed inset-0 overflow-hidden">
      <div
        class="bg-brand-100/40 absolute start-1/2 -top-40 h-[60rem] w-[60rem] -translate-x-1/2 rounded-full blur-3xl"
      />
      <div class="bg-brand-50 absolute end-0 bottom-0 h-96 w-96 rounded-full blur-3xl" />
    </div>

    <main class="relative mx-auto max-w-3xl px-6 py-16 sm:py-24">
      <!-- Brand -->
      <div class="mb-10 flex items-center gap-2.5">
        <div
          class="bg-brand-600 ring-brand-400/30 flex h-8 w-8 items-center justify-center rounded-md shadow-sm ring-1"
        >
          <span class="text-sm leading-none font-semibold text-white">D</span>
        </div>
        <span class="text-ink-700 text-xs font-semibold tracking-[0.28em] uppercase">Darejer</span>
      </div>

      <!-- Eyebrow -->
      <div
        class="border-paper-200 bg-card mb-5 inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 shadow-xs"
      >
        <Sparkles class="text-brand-600 h-3 w-3" />
        <span class="text-2xs text-ink-600 font-semibold tracking-[0.16em] uppercase">{{ __('Playground') }}</span>
      </div>

      <!-- Hero -->
      <h1 class="text-ink-900 text-4xl leading-[1.1] font-semibold tracking-tight sm:text-5xl">
        {{ __('Frontend stack') }}<br />
        <span class="text-brand-700">{{ __('is operational.') }}</span>
      </h1>
      <p class="text-ink-500 mt-5 max-w-xl text-base leading-relaxed">
        {{
          __(
            'All systems ready. Below is the live state of your runtime, dependencies, and shared Inertia props from the Darejer service provider.',
          )
        }}
      </p>

      <!-- Stack -->
      <section class="mt-10">
        <div class="mb-3 flex items-center justify-between">
          <h2 class="text-ink-500 text-[10px] font-semibold tracking-[0.18em] uppercase">
            {{ __('Runtime') }}
          </h2>
          <span class="text-2xs text-success-600 font-semibold tracking-[0.14em] uppercase">
            {{ __('All ready') }}
          </span>
        </div>
        <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3">
          <div
            v-for="check in checks"
            :key="check.label"
            class="border-paper-200 bg-card flex items-center justify-between gap-2 rounded-md border px-3 py-2.5 shadow-xs"
          >
            <span class="text-ink-800 truncate text-sm font-medium">{{ check.label }}</span>
            <CheckCircle class="text-success-600 h-4 w-4 shrink-0" />
          </div>
        </div>
      </section>

      <!-- Shared props -->
      <section class="border-paper-200 bg-card mt-8 rounded-lg border p-5 shadow-xs">
        <h3 class="text-ink-500 mb-3 text-[10px] font-semibold tracking-[0.18em] uppercase">
          {{ __('Inertia shared props') }}
        </h3>
        <dl class="grid grid-cols-[8rem_1fr] gap-x-3 gap-y-2.5 text-sm">
          <dt class="text-ink-500">{{ __('Languages') }}</dt>
          <dd class="flex flex-wrap gap-1.5">
            <span
              v-for="lang in page.props.darejer.languages"
              :key="lang"
              class="border-paper-200 bg-paper-100 text-2xs text-ink-700 inline-flex items-center rounded-sm border px-2 py-0.5 font-semibold tracking-wide uppercase"
            >
              {{ lang }}
            </span>
          </dd>
          <dt class="text-ink-500">{{ __('Default') }}</dt>
          <dd>
            <span
              class="border-brand-100 bg-brand-50 text-2xs text-brand-700 inline-flex items-center rounded-sm border px-2 py-0.5 font-semibold tracking-wide uppercase"
            >
              {{ page.props.darejer.default_language }}
            </span>
          </dd>
        </dl>
      </section>

      <!-- CTA -->
      <div class="mt-10">
        <button
          type="button"
          class="group bg-brand-600 hover:bg-brand-700 inline-flex h-11 items-center justify-center gap-2 rounded-md px-5 text-sm font-semibold text-white shadow-sm transition-colors"
        >
          {{ __('Continue to App') }}
          <ArrowRight
            class="h-4 w-4 transition-transform group-hover:translate-x-0.5 rtl:rotate-180 rtl:group-hover:-translate-x-0.5"
          />
        </button>
      </div>
    </main>
  </div>
</template>
