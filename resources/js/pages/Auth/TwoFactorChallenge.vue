<script setup lang="ts">
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AuthLayout from '@/layouts/AuthLayout.vue'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { ArrowRight, AlertCircle, ShieldCheck } from 'lucide-vue-next'
import useTranslation from '@/composables/useTranslation'

defineOptions({ layout: AuthLayout })

const { __ } = useTranslation()

const recovery = ref(false)

const form = useForm({
  code: '',
  recovery_code: '',
})

const heading = computed(() =>
  recovery.value ? __('Enter recovery code') : __('Two-factor verification'),
)

const subhead = computed(() =>
  recovery.value
    ? __('Use one of your saved recovery codes.')
    : __('Open your authenticator app and enter the 6-digit code.'),
)

function toggle() {
  recovery.value = !recovery.value
  form.reset('code', 'recovery_code')
  form.clearErrors()
}

function submit() {
  form.post(route('two-factor.login').toString(), {
    onFinish: () => form.reset('code', 'recovery_code'),
  })
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
        <ShieldCheck class="h-3 w-3" />
        {{ __('Two-factor') }}
      </span>
      <h1 class="text-3xl leading-tight font-semibold tracking-tight text-ink-900">
        {{ heading }}
      </h1>
      <p class="text-sm leading-relaxed text-ink-500">
        {{ subhead }}
      </p>
    </div>

    <form class="space-y-5" @submit.prevent="submit">
      <div v-if="!recovery" class="flex flex-col gap-1.5">
        <Label for="code" class="text-xs font-semibold text-ink-700">{{
          __('Authentication code')
        }}</Label>
        <Input
          id="code"
          v-model="form.code"
          type="text"
          inputmode="numeric"
          autocomplete="one-time-code"
          placeholder="123456"
          class="h-11 text-center font-mono text-lg tracking-[0.35em] tabular-nums"
          :class="{ 'border-danger-600 focus-visible:ring-danger-500/20': form.errors.code }"
        />
        <p v-if="form.errors.code" class="flex items-center gap-1 text-xs text-danger-600">
          <AlertCircle class="h-3 w-3" />
          {{ form.errors.code }}
        </p>
      </div>

      <div v-else class="flex flex-col gap-1.5">
        <Label for="recovery_code" class="text-xs font-semibold text-ink-700">{{
          __('Recovery code')
        }}</Label>
        <Input
          id="recovery_code"
          v-model="form.recovery_code"
          type="text"
          autocomplete="one-time-code"
          class="h-10 font-mono"
          :class="{
            'border-danger-600 focus-visible:ring-danger-500/20': form.errors.recovery_code,
          }"
        />
        <p v-if="form.errors.recovery_code" class="flex items-center gap-1 text-xs text-danger-600">
          <AlertCircle class="h-3 w-3" />
          {{ form.errors.recovery_code }}
        </p>
      </div>

      <button
        type="submit"
        class="group inline-flex h-11 w-full items-center justify-center gap-2 rounded-md bg-brand-600 px-4 text-sm font-semibold tracking-wide text-white shadow-sm transition-colors hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-60"
        :disabled="form.processing"
      >
        {{ form.processing ? __('Verifying') : __('Continue') }}
        <ArrowRight
          class="h-4 w-4 transition-transform group-hover:translate-x-0.5 rtl:rotate-180 rtl:group-hover:-translate-x-0.5"
        />
      </button>

      <button
        type="button"
        class="text-sm font-medium text-ink-500 transition-colors hover:text-brand-700"
        @click="toggle"
      >
        {{ recovery ? __('Use an authentication code') : __('Use a recovery code') }}
      </button>
    </form>

    <div
      class="flex items-center justify-between border-t border-paper-200 pt-5 text-2xs tracking-[0.18em] text-ink-400 uppercase tabular-nums"
    >
      <p>{{ __('© :year Darejer', { year: new Date().getFullYear() }) }}</p>
      <p>{{ __('Secured with TLS') }}</p>
    </div>
  </div>
</template>
