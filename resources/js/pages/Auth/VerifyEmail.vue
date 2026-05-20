<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { CheckCircle2, ArrowRight, MailCheck } from 'lucide-vue-next'
import { computed } from 'vue'
import useTranslation from '@/composables/useTranslation'
import AuthLayout from '@/layouts/AuthLayout.vue'

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
          class="bg-brand-600 ring-brand-400/30 flex h-7 w-7 items-center justify-center rounded-md shadow-sm ring-1"
        >
          <span class="text-base leading-none font-semibold text-white">D</span>
        </div>
        <span class="text-ink-700 text-xs font-semibold tracking-[0.28em] uppercase">Darejer</span>
      </div>
      <span class="text-2xs text-brand-700 inline-flex items-center gap-1 font-semibold tracking-[0.24em] uppercase">
        <MailCheck class="h-3 w-3" />
        {{ __('One last step') }}
      </span>
      <h1 class="text-ink-900 text-3xl leading-tight font-semibold tracking-tight">
        {{ __('Verify your email') }}
      </h1>
      <p class="text-ink-500 text-sm leading-relaxed">
        {{ __("Check your inbox for the verification link. Didn't get it? Resend below.") }}
      </p>
    </div>

    <div
      v-if="verificationSent"
      class="border-success-100 bg-success-50 flex items-start gap-2.5 rounded-md border px-3.5 py-3"
    >
      <CheckCircle2 class="text-success-600 mt-0.5 h-4 w-4 shrink-0" />
      <p class="text-success-700 text-sm leading-relaxed">
        {{ __('A fresh verification link has been sent to your email address.') }}
      </p>
    </div>

    <div class="space-y-3">
      <button
        type="button"
        class="group bg-brand-600 hover:bg-brand-700 inline-flex h-11 w-full items-center justify-center gap-2 rounded-md px-4 text-sm font-semibold tracking-wide text-white shadow-sm transition-colors disabled:cursor-not-allowed disabled:opacity-60"
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
        class="text-ink-500 hover:text-brand-700 text-sm font-medium transition-colors"
        @click="logout"
      >
        {{ __('Sign out') }}
      </button>
    </div>

    <div
      class="border-paper-200 text-2xs text-ink-400 flex items-center justify-between border-t pt-5 tracking-[0.18em] uppercase tabular-nums"
    >
      <p>{{ __('© :year Darejer', { year: new Date().getFullYear() }) }}</p>
      <p>{{ __('Secured with TLS') }}</p>
    </div>
  </div>
</template>
