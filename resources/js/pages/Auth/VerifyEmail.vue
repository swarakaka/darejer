<script setup lang="ts">
import { computed } from 'vue'
import { useForm }  from '@inertiajs/vue3'
import AuthLayout   from '@/layouts/AuthLayout.vue'
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
                <div class="w-7 h-7 rounded-md bg-brand-600 flex items-center justify-center ring-1 ring-brand-400/30 shadow-sm">
                    <span class="text-white text-base font-semibold leading-none">D</span>
                </div>
                <span class="text-xs font-semibold tracking-[0.28em] uppercase text-ink-700">Darejer</span>
            </div>
            <span class="inline-flex items-center gap-1 text-2xs font-semibold uppercase tracking-[0.24em] text-brand-700">
                <MailCheck class="w-3 h-3" />
                {{ __('One last step') }}
            </span>
            <h1 class="text-3xl font-semibold leading-tight text-ink-900 tracking-tight">
                {{ __('Verify your email') }}
            </h1>
            <p class="text-sm text-ink-500 leading-relaxed">
                {{ __('Check your inbox for the verification link. Didn\'t get it? Resend below.') }}
            </p>
        </div>

        <div
            v-if="verificationSent"
            class="flex items-start gap-2.5 px-3.5 py-3 rounded-md bg-success-50 border border-success-100"
        >
            <CheckCircle2 class="w-4 h-4 text-success-600 mt-0.5 shrink-0" />
            <p class="text-sm text-success-700 leading-relaxed">
                {{ __('A fresh verification link has been sent to your email address.') }}
            </p>
        </div>

        <div class="space-y-3">
            <button
                type="button"
                class="group w-full inline-flex items-center justify-center gap-2 h-11 px-4 rounded-md
                       bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold tracking-wide
                       shadow-sm transition-colors disabled:opacity-60 disabled:cursor-not-allowed"
                :disabled="form.processing"
                @click="send"
            >
                {{ __('Resend verification email') }}
                <ArrowRight class="w-4 h-4 transition-transform group-hover:translate-x-0.5 rtl:rotate-180 rtl:group-hover:-translate-x-0.5" />
            </button>

            <button
                type="button"
                class="text-sm font-medium text-ink-500 hover:text-brand-700 transition-colors"
                @click="logout"
            >
                {{ __('Sign out') }}
            </button>
        </div>

        <div class="flex items-center justify-between text-2xs uppercase tracking-[0.18em] text-ink-400 tabular-nums pt-5 border-t border-paper-200">
            <p>{{ __('© :year Darejer', { year: new Date().getFullYear() }) }}</p>
            <p>{{ __('Secured with TLS') }}</p>
        </div>
    </div>
</template>
