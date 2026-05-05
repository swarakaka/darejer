<script setup lang="ts">
import { usePage } from '@inertiajs/vue3'
import { CheckCircle, ArrowRight, Sparkles } from 'lucide-vue-next'
import type { DarejerSharedProps } from '@/types/darejer'
import useTranslation from '@/composables/useTranslation'

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
      <div
        class="absolute start-1/2 -top-40 h-[60rem] w-[60rem] -translate-x-1/2 rounded-full bg-brand-100/40 blur-3xl"
      />
      <div class="absolute end-0 bottom-0 h-96 w-96 rounded-full bg-brand-50 blur-3xl" />
    </div>

    <main class="relative mx-auto max-w-3xl px-6 py-16 sm:py-24">
      <!-- Brand -->
      <div class="mb-10 flex items-center gap-2.5">
        <div
          class="flex h-8 w-8 items-center justify-center rounded-md bg-brand-600 shadow-sm ring-1 ring-brand-400/30"
        >
          <span class="text-sm leading-none font-semibold text-white">D</span>
        </div>
        <span class="text-xs font-semibold tracking-[0.28em] text-ink-700 uppercase">Darejer</span>
      </div>

      <!-- Eyebrow -->
      <div
        class="mb-5 inline-flex items-center gap-1.5 rounded-full border border-paper-200 bg-card px-2.5 py-1 shadow-xs"
      >
        <Sparkles class="h-3 w-3 text-brand-600" />
        <span class="text-2xs font-semibold tracking-[0.16em] text-ink-600 uppercase">{{
          __('Playground')
        }}</span>
      </div>

      <!-- Hero -->
      <h1 class="text-4xl leading-[1.1] font-semibold tracking-tight text-ink-900 sm:text-5xl">
        {{ __('Frontend stack') }}<br />
        <span class="text-brand-700">{{ __('is operational.') }}</span>
      </h1>
      <p class="mt-5 max-w-xl text-base leading-relaxed text-ink-500">
        {{
          __(
            'All systems ready. Below is the live state of your runtime, dependencies, and shared Inertia props from the Darejer service provider.',
          )
        }}
      </p>

      <!-- Stack -->
      <section class="mt-10">
        <div class="mb-3 flex items-center justify-between">
          <h2 class="text-[10px] font-semibold tracking-[0.18em] text-ink-500 uppercase">
            {{ __('Runtime') }}
          </h2>
          <span class="text-2xs font-semibold tracking-[0.14em] text-success-600 uppercase">
            {{ __('All ready') }}
          </span>
        </div>
        <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3">
          <div
            v-for="check in checks"
            :key="check.label"
            class="flex items-center justify-between gap-2 rounded-md border border-paper-200 bg-card px-3 py-2.5 shadow-xs"
          >
            <span class="truncate text-sm font-medium text-ink-800">{{ check.label }}</span>
            <CheckCircle class="h-4 w-4 shrink-0 text-success-600" />
          </div>
        </div>
      </section>

      <!-- Shared props -->
      <section class="mt-8 rounded-lg border border-paper-200 bg-card p-5 shadow-xs">
        <h3 class="mb-3 text-[10px] font-semibold tracking-[0.18em] text-ink-500 uppercase">
          {{ __('Inertia shared props') }}
        </h3>
        <dl class="grid grid-cols-[8rem_1fr] gap-x-3 gap-y-2.5 text-sm">
          <dt class="text-ink-500">{{ __('Languages') }}</dt>
          <dd class="flex flex-wrap gap-1.5">
            <span
              v-for="lang in page.props.darejer.languages"
              :key="lang"
              class="inline-flex items-center rounded-sm border border-paper-200 bg-paper-100 px-2 py-0.5 text-2xs font-semibold tracking-wide text-ink-700 uppercase"
            >
              {{ lang }}
            </span>
          </dd>
          <dt class="text-ink-500">{{ __('Default') }}</dt>
          <dd>
            <span
              class="inline-flex items-center rounded-sm border border-brand-100 bg-brand-50 px-2 py-0.5 text-2xs font-semibold tracking-wide text-brand-700 uppercase"
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
          class="group inline-flex h-11 items-center justify-center gap-2 rounded-md bg-brand-600 px-5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-brand-700"
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
