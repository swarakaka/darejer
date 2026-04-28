<script setup lang="ts">
import { useForm, Link }    from '@inertiajs/vue3'
import AuthLayout           from '@/layouts/AuthLayout.vue'
import { Input }            from '@/components/ui/input'
import { Label }            from '@/components/ui/label'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { CheckCircle2, ArrowLeft, ArrowRight } from 'lucide-vue-next'
import useTranslation       from '@/composables/useTranslation'

defineOptions({ layout: AuthLayout })

const { __ } = useTranslation()

defineProps<{ status?: string }>()

const form = useForm({ email: '' })

function submit() {
    form.post(route('password.email').toString())
}
</script>

<template>
    <div class="space-y-8 p-6 bg-white">

        <div class="flex flex-col items-center gap-6 text-center">
            <div class="flex items-center gap-2.5">
                <div class="w-6 h-6 rounded-sm bg-brand-600 flex items-center justify-center">
                    <span class="font-serif text-white text-base leading-none translate-y-[1px]">D</span>
                </div>
                <span class="text-sm font-medium tracking-[0.24em] uppercase text-ink-700">Darejer</span>
            </div>
            <h1 class="font-serif text-3xl leading-tight text-ink-900 tracking-tight">
                {{ __('Forgot password') }}
            </h1>
        </div>

        <Alert v-if="status" class="py-2.5 bg-success-50 border-success-100">
            <CheckCircle2 class="w-4 h-4 text-success-600" />
            <AlertDescription class="text-sm text-success-700">{{ status }}</AlertDescription>
        </Alert>

        <form class="space-y-5" @submit.prevent="submit">

            <div class="flex flex-col gap-1.5">
                <Label for="email">{{ __('Email') }}</Label>
                <Input
                    id="email"
                    v-model="form.email"
                    type="email"
                    autocomplete="email"
                    :placeholder="__('you@company.com')"
                    :class="{ 'border-danger-600': form.errors.email }"
                />
                <p v-if="form.errors.email" class="text-xs text-danger-600">{{ form.errors.email }}</p>
            </div>

            <button
                type="submit"
                class="group w-full inline-flex items-center justify-center gap-2 h-10 px-4 rounded-sm
                       bg-brand-600 hover:bg-brand-700 text-paper-50 text-sm font-medium tracking-wide
                       border border-transparent transition-colors disabled:opacity-60"
                :disabled="form.processing"
            >
                {{ form.processing ? __('Sending') : __('Send reset link') }}
                <ArrowRight class="w-3.5 h-3.5 transition-transform group-hover:translate-x-0.5" />
            </button>

            <Link
                :href="route('login').toString()"
                class="inline-flex items-center gap-1.5 text-sm text-ink-500 hover:text-brand-600 transition-colors"
            >
                <ArrowLeft class="w-3.5 h-3.5" />
                {{ __('Back to sign in') }}
            </Link>

        </form>

        <div class="flex items-center justify-between *:text-ink-400  tabular-nums pt-4 border-t border-paper-200">
            <p>Darejer</p>
            <p>{{ new Date().getFullYear() }}</p>
        </div>
    </div>
</template>
