<script setup lang="ts">
import { useForm, Link, usePage } from '@inertiajs/vue3'
import { CheckCircle2, ArrowLeft, ArrowRight, AlertCircle } from 'lucide-vue-next'
import { computed } from 'vue'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import useTranslation from '@/composables/useTranslation'
import AuthLayout from '@/layouts/AuthLayout.vue'
import type { DarejerSharedProps } from '@/types/darejer'

defineOptions({ layout: AuthLayout })

const { __ } = useTranslation()
const page = usePage<DarejerSharedProps>()
const appName = computed(() => page.props.darejer?.app_name ?? 'Darejer')

defineProps<{ status?: string }>()

const form = useForm({ email: '' })

function submit() {
  form.post(route('password.email').toString())
}
</script>

<template>
  <div class="space-y-7 p-4">
    <div class="flex flex-col gap-3 lg:gap-2">
      <div class="flex items-center gap-2.5 lg:hidden">
        <div
          class="bg-brand-600 ring-brand-400/30 flex h-7 w-7 items-center justify-center rounded-md shadow-sm ring-1"
        >
          <span class="text-base leading-none font-semibold text-white">D</span>
        </div>
        <span class="text-ink-700 text-xs font-semibold tracking-[0.28em] uppercase">{{ appName }}</span>
      </div>
      <h1 class="text-ink-900 text-2xl leading-tight font-semibold tracking-tight">
        {{ __('Forgot password?') }}
      </h1>
      <p class="text-ink-500 text-sm leading-relaxed">
        {{ __("Enter your email and we'll send a reset link.") }}
      </p>
    </div>

    <div v-if="status" class="border-success-100 bg-success-50 flex items-start gap-2.5 rounded-md border px-3.5 py-3">
      <CheckCircle2 class="text-success-600 mt-0.5 h-4 w-4 shrink-0" />
      <p class="text-success-700 text-sm leading-relaxed">{{ status }}</p>
    </div>

    <form class="space-y-5" @submit.prevent="submit">
      <div class="flex flex-col gap-1.5">
        <Label for="email" class="text-ink-700 text-xs font-semibold">{{ __('Email') }}</Label>
        <Input
          id="email"
          v-model="form.email"
          type="email"
          autocomplete="email"
          :placeholder="__('you@company.com')"
          class="h-10"
          :class="{ 'border-danger-600 focus-visible:ring-danger-500/20': form.errors.email }"
        />
        <p v-if="form.errors.email" class="text-danger-600 flex items-center gap-1 text-xs">
          <AlertCircle class="h-3 w-3" />
          {{ form.errors.email }}
        </p>
      </div>

      <button
        type="submit"
        class="group bg-brand-600 hover:bg-brand-700 inline-flex h-11 w-full items-center justify-center gap-2 rounded-md px-4 text-sm font-semibold tracking-wide text-white shadow-sm transition-colors disabled:cursor-not-allowed disabled:opacity-60"
        :disabled="form.processing"
      >
        {{ form.processing ? __('Sending') : __('Send reset link') }}
        <ArrowRight
          class="h-4 w-4 transition-transform group-hover:translate-x-0.5 rtl:rotate-180 rtl:group-hover:-translate-x-0.5"
        />
      </button>

      <Link
        :href="route('login').toString()"
        class="text-ink-500 hover:text-brand-700 inline-flex items-center gap-1.5 text-sm font-medium transition-colors"
      >
        <ArrowLeft class="h-3.5 w-3.5 rtl:rotate-180" />
        {{ __('Back to sign in') }}
      </Link>
    </form>

    <div
      class="border-paper-200 text-2xs text-ink-400 flex items-center justify-between border-t pt-5 tracking-[0.18em] uppercase tabular-nums"
    >
      <p>{{ __('© :year', { year: new Date().getFullYear() }) }}</p>
      <p>{{ appName }}</p>
    </div>
  </div>
</template>
