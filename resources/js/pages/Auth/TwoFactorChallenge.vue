<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { ArrowRight, AlertCircle, ShieldCheck } from 'lucide-vue-next'
import { ref, computed } from 'vue'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import useTranslation from '@/composables/useTranslation'
import AuthLayout from '@/layouts/AuthLayout.vue'

defineOptions({ layout: AuthLayout })

const { __ } = useTranslation()

const recovery = ref(false)

const form = useForm({
  code: '',
  recovery_code: '',
})

const heading = computed(() => (recovery.value ? __('Enter recovery code') : __('Two-factor verification')))

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
          class="bg-brand-600 ring-brand-400/30 flex h-7 w-7 items-center justify-center rounded-md shadow-sm ring-1"
        >
          <span class="text-base leading-none font-semibold text-white">D</span>
        </div>
        <span class="text-ink-700 text-xs font-semibold tracking-[0.28em] uppercase">Darejer</span>
      </div>
      <span class="text-2xs text-brand-700 inline-flex items-center gap-1 font-semibold tracking-[0.24em] uppercase">
        <ShieldCheck class="h-3 w-3" />
        {{ __('Two-factor') }}
      </span>
      <h1 class="text-ink-900 text-3xl leading-tight font-semibold tracking-tight">
        {{ heading }}
      </h1>
      <p class="text-ink-500 text-sm leading-relaxed">
        {{ subhead }}
      </p>
    </div>

    <form class="space-y-5" @submit.prevent="submit">
      <div v-if="!recovery" class="flex flex-col gap-1.5">
        <Label for="code" class="text-ink-700 text-xs font-semibold">{{ __('Authentication code') }}</Label>
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
        <p v-if="form.errors.code" class="text-danger-600 flex items-center gap-1 text-xs">
          <AlertCircle class="h-3 w-3" />
          {{ form.errors.code }}
        </p>
      </div>

      <div v-else class="flex flex-col gap-1.5">
        <Label for="recovery_code" class="text-ink-700 text-xs font-semibold">{{ __('Recovery code') }}</Label>
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
        <p v-if="form.errors.recovery_code" class="text-danger-600 flex items-center gap-1 text-xs">
          <AlertCircle class="h-3 w-3" />
          {{ form.errors.recovery_code }}
        </p>
      </div>

      <button
        type="submit"
        class="group bg-brand-600 hover:bg-brand-700 inline-flex h-11 w-full items-center justify-center gap-2 rounded-md px-4 text-sm font-semibold tracking-wide text-white shadow-sm transition-colors disabled:cursor-not-allowed disabled:opacity-60"
        :disabled="form.processing"
      >
        {{ form.processing ? __('Verifying') : __('Continue') }}
        <ArrowRight
          class="h-4 w-4 transition-transform group-hover:translate-x-0.5 rtl:rotate-180 rtl:group-hover:-translate-x-0.5"
        />
      </button>

      <button
        type="button"
        class="text-ink-500 hover:text-brand-700 text-sm font-medium transition-colors"
        @click="toggle"
      >
        {{ recovery ? __('Use an authentication code') : __('Use a recovery code') }}
      </button>
    </form>

    <div
      class="border-paper-200 text-2xs text-ink-400 flex items-center justify-between border-t pt-5 tracking-[0.18em] uppercase tabular-nums"
    >
      <p>{{ __('© :year Darejer', { year: new Date().getFullYear() }) }}</p>
      <p>{{ __('Secured with TLS') }}</p>
    </div>
  </div>
</template>
