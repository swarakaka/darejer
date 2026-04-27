<script setup lang="ts">
import { useForm, Link }  from '@inertiajs/vue3'
import AuthLayout         from '@/layouts/AuthLayout.vue'
import { Input }          from '@/components/ui/input'
import { Label }          from '@/components/ui/label'
import { ArrowLeft, ArrowRight } from 'lucide-vue-next'
import useTranslation     from '@/composables/useTranslation'

defineOptions({ layout: AuthLayout })

const { __ } = useTranslation()

const form = useForm({
    name:                  '',
    email:                 '',
    password:              '',
    password_confirmation: '',
})

function submit() {
    form.post(route('register').toString(), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    })
}
</script>

<template>
    <div class="space-y-8">

        <div>
            <div class="text-[10px] font-semibold uppercase tracking-[0.18em] text-brand-600 mb-2">{{ __('Entry · New Account') }}</div>
            <h1 class="font-serif text-3xl leading-tight text-ink-900 tracking-tight">
                {{ __('Open an') }} <em class="italic text-brand-600">{{ __('account') }}</em>.
            </h1>
            <p class="text-sm text-ink-500 mt-2">
                {{ __('Fill in your details to create a new ledger.') }}
            </p>
        </div>

        <form class="space-y-5" @submit.prevent="submit">

            <div class="flex flex-col gap-1.5">
                <Label for="name">{{ __('Name') }}</Label>
                <Input
                    id="name"
                    v-model="form.name"
                    type="text"
                    autocomplete="name"
                    :class="{ 'border-danger-600': form.errors.name }"
                />
                <p v-if="form.errors.name" class="text-xs text-danger-600">{{ form.errors.name }}</p>
            </div>

            <div class="flex flex-col gap-1.5">
                <Label for="email">{{ __('Email') }}</Label>
                <Input
                    id="email"
                    v-model="form.email"
                    type="email"
                    autocomplete="email"
                    :class="{ 'border-danger-600': form.errors.email }"
                />
                <p v-if="form.errors.email" class="text-xs text-danger-600">{{ form.errors.email }}</p>
            </div>

            <div class="flex flex-col gap-1.5">
                <Label for="password">{{ __('Password') }}</Label>
                <Input
                    id="password"
                    v-model="form.password"
                    type="password"
                    autocomplete="new-password"
                    :class="{ 'border-danger-600': form.errors.password }"
                />
                <p v-if="form.errors.password" class="text-xs text-danger-600">{{ form.errors.password }}</p>
            </div>

            <div class="flex flex-col gap-1.5">
                <Label for="password_confirmation">{{ __('Confirm password') }}</Label>
                <Input
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    autocomplete="new-password"
                />
            </div>

            <button
                type="submit"
                class="group w-full inline-flex items-center justify-center gap-2 h-10 px-4 rounded-sm
                       bg-ink-900 hover:bg-ink-800 text-paper-50 text-sm font-medium tracking-wide
                       border border-transparent transition-colors disabled:opacity-60"
                :disabled="form.processing"
            >
                {{ form.processing ? __('Creating') : __('Create account') }}
                <ArrowRight class="w-3.5 h-3.5 transition-transform group-hover:translate-x-0.5" />
            </button>

        </form>

        <Link
            :href="route('login').toString()"
            class="inline-flex items-center gap-1.5 text-sm text-ink-500 hover:text-brand-600 transition-colors"
        >
            <ArrowLeft class="w-3.5 h-3.5" />
            {{ __('Back to sign in') }}
        </Link>
    </div>
</template>
