<script setup lang="ts">
import { computed } from 'vue'
import { useForm }  from '@inertiajs/vue3'
import AuthLayout   from '@/layouts/AuthLayout.vue'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { CheckCircle2 } from 'lucide-vue-next'
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
    <div class="space-y-8">

        <div>
            <div class="text-[10px] font-semibold uppercase tracking-[0.18em] text-brand-600 mb-2">{{ __('Entry · Verify') }}</div>
            <h1 class="font-serif text-3xl leading-tight text-ink-900 tracking-tight">
                {{ __('Verify your') }} <em class="italic text-brand-600">{{ __('email address') }}</em>.
            </h1>
            <p class="text-sm text-ink-500 mt-2">
                {{ __("Before continuing, please check your inbox for a verification link. If you didn't receive it, we can send another.") }}
            </p>
        </div>

        <Alert v-if="verificationSent" class="py-2.5 bg-success-50 border-success-100">
            <CheckCircle2 class="w-4 h-4 text-success-600" />
            <AlertDescription class="text-sm text-success-700">
                {{ __('A fresh verification link has been sent to your email address.') }}
            </AlertDescription>
        </Alert>

        <div class="flex items-center gap-3">
            <button
                type="button"
                class="inline-flex items-center justify-center h-10 px-4 rounded-sm
                       bg-ink-900 hover:bg-ink-800 text-paper-50 text-sm font-medium tracking-wide
                       border border-transparent transition-colors disabled:opacity-60"
                :disabled="form.processing"
                @click="send"
            >
                {{ __('Resend verification email') }}
            </button>

            <button
                type="button"
                class="text-sm text-ink-500 hover:text-brand-600 transition-colors"
                @click="logout"
            >
                {{ __('Sign out') }}
            </button>
        </div>
    </div>
</template>
