<script setup lang="ts">
import {Link, useForm} from '@inertiajs/vue3'
import AuthLayout           from '@/layouts/AuthLayout.vue'
import { Input }            from '@/components/ui/input'
import { Label }            from '@/components/ui/label'
import { Checkbox }         from '@/components/ui/checkbox'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { AlertCircle, ArrowRight } from 'lucide-vue-next'
import useTranslation       from '@/composables/useTranslation'

defineOptions({ layout: AuthLayout })

const { __ } = useTranslation()

const form = useForm({
    username: '',
    password: '',
    remember: false,
})

function submit() {
    form.post(route('login').toString(), {
        onFinish: () => form.reset('password'),
    })
}
</script>

<template>
    <div class="space-y-8 p-6 bg-white">

        <div class="flex flex-col items-center gap-6 text-center">
            <div class="flex items-center gap-2.5">
                <div class="w-6 h-6 rounded-sm bg-brand-600 flex items-center justify-center">
                    <span class="text-white text-base leading-none translate-y-[1px]">D</span>
                </div>
                <span class="text-sm font-medium tracking-[0.24em] uppercase text-ink-700">Darejer</span>
            </div>
            <h1 class="text-3xl leading-tight text-ink-900 tracking-tight">
                {{ __('Login') }}
            </h1>
        </div>

        <form class="space-y-5" @submit.prevent="submit">

            <div class="flex flex-col gap-1.5">
                <Label for="username">{{ __('Username') }}</Label>
                <Input
                    id="username"
                    v-model="form.username"
                    type="text"
                    autocomplete="username"
                    :placeholder="__('your.username')"
                    :class="{ 'border-danger-600': form.errors.username }"
                />
              <p v-if="form.errors.username" class="text-xs text-danger-600">
                {{ form.errors.username }}
              </p>
            </div>

            <div class="flex flex-col gap-1.5">
                <div class="flex items-center justify-between">
                    <Label for="password">{{ __('Password') }}</Label>
                    <Link
                        :href="route('password.request').toString()"
                        class="text-xs text-ink-500 hover:text-brand-600 transition-colors"
                    >
                        {{ __('Forgot password?') }}
                    </Link>
                </div>
                <Input
                    id="password"
                    v-model="form.password"
                    type="password"
                    autocomplete="current-password"
                    placeholder="••••••••"
                    :class="{ 'border-danger-600': form.errors.password }"
                />
                <p v-if="form.errors.password" class="text-xs text-danger-600">
                    {{ form.errors.password }}
                </p>
            </div>

            <div class="flex items-center gap-2">
                <Checkbox id="remember" v-model:checked="form.remember" />
                <Label for="remember" class="text-sm text-ink-600 cursor-pointer">
                    {{ __('Keep me signed in') }}
                </Label>
            </div>

            <button
                type="submit"
                class="group w-full inline-flex items-center justify-center gap-2 h-10 px-4 rounded-sm
                       bg-brand-600 hover:bg-brand-700 text-paper-50 text-sm font-medium tracking-wide
                       border border-transparent transition-colors disabled:opacity-60"
                :disabled="form.processing"
            >
                {{ form.processing ? __('Reconciling') : __('Sign in') }}
                <ArrowRight class="w-3.5 h-3.5 transition-transform group-hover:translate-x-0.5" />
            </button>

        </form>

       <div class="flex items-center justify-between *:text-ink-400  tabular-nums pt-4 border-t border-paper-200">
         <p>Darejer</p>
         <p>{{ new Date().getFullYear() }}</p>
       </div>
    </div>
</template>
