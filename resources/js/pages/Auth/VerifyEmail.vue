<script setup lang="ts">
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AuthLayout from '@/layouts/AuthLayout.vue'
import { CheckCircle2, ArrowRight, MailCheck } from 'lucide-vue-next'
import useTranslation from '@/composables/useTranslation'

defineOptions({ layout: AuthLayout })

const { __ } = useTranslation()

const props = defineProps<{ status?: string }>()

const form = useForm({})

const verificationSent = computed(() => props.status === 'verification-link-sent')

function send() {
  form.post(route('verification.send').toString())
}

function logout() {
  form.post(route('logout').toString())
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
      <span
        class="inline-flex items-center gap-1 text-2xs font-semibold tracking-[0.24em] text-brand-700 uppercase"
      >
        <MailCheck class="h-3 w-3" />
        {{ __('One last step') }}
      </span>
      <h1 class="text-3xl leading-tight font-semibold tracking-tight text-ink-900">
        {{ __('Verify your email') }}
      </h1>
      <p class="text-sm leading-relaxed text-ink-500">
        {{ __("Check your inbox for the verification link. Didn't get it? Resend below.") }}
      </p>
    </div>

    <div
      v-if="verificationSent"
      class="flex items-start gap-2.5 rounded-md border border-success-100 bg-success-50 px-3.5 py-3"
    >
      <CheckCircle2 class="mt-0.5 h-4 w-4 shrink-0 text-success-600" />
      <p class="text-sm leading-relaxed text-success-700">
        {{ __('A fresh verification link has been sent to your email address.') }}
      </p>
    </div>

    <div class="space-y-3">
      <button
        type="button"
        class="group inline-flex h-11 w-full items-center justify-center gap-2 rounded-md bg-brand-600 px-4 text-sm font-semibold tracking-wide text-white shadow-sm transition-colors hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-60"
        :disabled="form.processing"
        @click="send"
      >
        {{ __('Resend verification email') }}
        <ArrowRight
          class="h-4 w-4 transition-transform group-hover:translate-x-0.5 rtl:rotate-180 rtl:group-hover:-translate-x-0.5"
        />
      </button>

      <button
        type="button"
        class="text-sm font-medium text-ink-500 transition-colors hover:text-brand-700"
        @click="logout"
      >
        {{ __('Sign out') }}
      </button>
    </div>

    <div
      class="flex items-center justify-between border-t border-paper-200 pt-5 text-2xs tracking-[0.18em] text-ink-400 uppercase tabular-nums"
    >
      <p>{{ __('© :year Darejer', { year: new Date().getFullYear() }) }}</p>
      <p>{{ __('Secured with TLS') }}</p>
    </div>
  </div>
</template>
