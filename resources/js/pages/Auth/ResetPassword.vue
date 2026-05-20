<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { ArrowRight, AlertCircle } from 'lucide-vue-next'
import { Input, InputPassword } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import useTranslation from '@/composables/useTranslation'
import AuthLayout from '@/layouts/AuthLayout.vue'

defineOptions({ layout: AuthLayout })

const { __ } = useTranslation()

const props = defineProps<{ token: string; email: string }>()

const form = useForm({
  token: props.token,
  email: props.email,
  password: '',
  password_confirmation: '',
})

function submit() {
  form.post(route('password.update').toString(), {
    onFinish: () => form.reset('password', 'password_confirmation'),
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
      <span class="text-2xs text-brand-700 font-semibold tracking-[0.24em] uppercase">{{ __('Almost there') }}</span>
      <h1 class="text-ink-900 text-3xl leading-tight font-semibold tracking-tight">
        {{ __('Reset your password') }}
      </h1>
      <p class="text-ink-500 text-sm leading-relaxed">
        {{ __("Choose a strong password you haven't used before.") }}
      </p>
    </div>

    <form class="space-y-4" @submit.prevent="submit">
      <div class="flex flex-col gap-1.5">
        <Label for="email" class="text-ink-700 text-xs font-semibold">{{ __('Email') }}</Label>
        <Input
          id="email"
          v-model="form.email"
          type="email"
          readonly
          class="bg-paper-100 text-ink-500 h-10 tabular-nums"
        />
        <p v-if="form.errors.email" class="text-danger-600 flex items-center gap-1 text-xs">
          <AlertCircle class="h-3 w-3" />
          {{ form.errors.email }}
        </p>
      </div>

      <div class="flex flex-col gap-1.5">
        <Label for="password" class="text-ink-700 text-xs font-semibold">{{ __('New password') }}</Label>
        <InputPassword
          id="password"
          v-model="form.password"
          autocomplete="new-password"
          placeholder="••••••••"
          class="h-10"
          :class="{ 'border-danger-600 focus-visible:ring-danger-500/20': form.errors.password }"
        />
        <p v-if="form.errors.password" class="text-danger-600 flex items-center gap-1 text-xs">
          <AlertCircle class="h-3 w-3" />
          {{ form.errors.password }}
        </p>
      </div>

      <div class="flex flex-col gap-1.5">
        <Label for="password_confirmation" class="text-ink-700 text-xs font-semibold">{{
          __('Confirm password')
        }}</Label>
        <InputPassword
          id="password_confirmation"
          v-model="form.password_confirmation"
          autocomplete="new-password"
          placeholder="••••••••"
          class="h-10"
          :class="{
            'border-danger-600 focus-visible:ring-danger-500/20': form.errors.password_confirmation,
          }"
        />
        <p v-if="form.errors.password_confirmation" class="text-danger-600 flex items-center gap-1 text-xs">
          <AlertCircle class="h-3 w-3" />
          {{ form.errors.password_confirmation }}
        </p>
      </div>

      <button
        type="submit"
        class="group bg-brand-600 hover:bg-brand-700 mt-2 inline-flex h-11 w-full items-center justify-center gap-2 rounded-md px-4 text-sm font-semibold tracking-wide text-white shadow-sm transition-colors disabled:cursor-not-allowed disabled:opacity-60"
        :disabled="form.processing"
      >
        {{ form.processing ? __('Saving') : __('Save new password') }}
        <ArrowRight
          class="h-4 w-4 transition-transform group-hover:translate-x-0.5 rtl:rotate-180 rtl:group-hover:-translate-x-0.5"
        />
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
