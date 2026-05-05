<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3'
import AuthLayout from '@/layouts/AuthLayout.vue'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { CheckCircle2, ArrowLeft, ArrowRight, AlertCircle } from 'lucide-vue-next'
import useTranslation from '@/composables/useTranslation'

defineOptions({ layout: AuthLayout })

const { __ } = useTranslation()

defineProps<{ status?: string }>()

const form = useForm({ email: '' })

function submit() {
  form.post(route('password.email').toString())
}
</script>

<template>
  <div class="space-y-7">
    <div class="flex flex-col gap-3 lg:gap-2">
      <div class="flex items-center gap-2.5 lg:hidden">
        <div
          class="flex h-7 w-7 items-center justify-center rounded-md bg-brand-600 shadow-sm ring-1 ring-brand-400/30"
        >
          <span class="text-base leading-none font-semibold text-white">D</span>
        </div>
        <span class="text-xs font-semibold tracking-[0.28em] text-ink-700 uppercase">Darejer</span>
      </div>
      <span class="text-2xs font-semibold tracking-[0.24em] text-brand-700 uppercase">{{
        __('Recovery')
      }}</span>
      <h1 class="text-3xl leading-tight font-semibold tracking-tight text-ink-900">
        {{ __('Forgot password?') }}
      </h1>
      <p class="text-sm leading-relaxed text-ink-500">
        {{ __("Enter your email and we'll send a reset link.") }}
      </p>
    </div>

    <div
      v-if="status"
      class="flex items-start gap-2.5 rounded-md border border-success-100 bg-success-50 px-3.5 py-3"
    >
      <CheckCircle2 class="mt-0.5 h-4 w-4 shrink-0 text-success-600" />
      <p class="text-sm leading-relaxed text-success-700">{{ status }}</p>
    </div>

    <form class="space-y-5" @submit.prevent="submit">
      <div class="flex flex-col gap-1.5">
        <Label for="email" class="text-xs font-semibold text-ink-700">{{ __('Email') }}</Label>
        <Input
          id="email"
          v-model="form.email"
          type="email"
          autocomplete="email"
          :placeholder="__('you@company.com')"
          class="h-10"
          :class="{ 'border-danger-600 focus-visible:ring-danger-500/20': form.errors.email }"
        />
        <p v-if="form.errors.email" class="flex items-center gap-1 text-xs text-danger-600">
          <AlertCircle class="h-3 w-3" />
          {{ form.errors.email }}
        </p>
      </div>

      <button
        type="submit"
        class="group inline-flex h-11 w-full items-center justify-center gap-2 rounded-md bg-brand-600 px-4 text-sm font-semibold tracking-wide text-white shadow-sm transition-colors hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-60"
        :disabled="form.processing"
      >
        {{ form.processing ? __('Sending') : __('Send reset link') }}
        <ArrowRight
          class="h-4 w-4 transition-transform group-hover:translate-x-0.5 rtl:rotate-180 rtl:group-hover:-translate-x-0.5"
        />
      </button>

      <Link
        :href="route('login').toString()"
        class="inline-flex items-center gap-1.5 text-sm font-medium text-ink-500 transition-colors hover:text-brand-700"
      >
        <ArrowLeft class="h-3.5 w-3.5 rtl:rotate-180" />
        {{ __('Back to sign in') }}
      </Link>
    </form>

    <div
      class="flex items-center justify-between border-t border-paper-200 pt-5 text-2xs tracking-[0.18em] text-ink-400 uppercase tabular-nums"
    >
      <p>{{ __('© :year Darejer', { year: new Date().getFullYear() }) }}</p>
      <p>{{ __('Secured with TLS') }}</p>
    </div>
  </div>
</template>
