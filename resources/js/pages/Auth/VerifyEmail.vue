<script setup lang="ts">
import { computed } from 'vue'
import { useForm }  from '@inertiajs/vue3'
import AuthLayout   from '@/layouts/AuthLayout.vue'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { CheckCircle2, ArrowRight } from 'lucide-vue-next'
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
    <div class="space-y-8 p-6 bg-white">

        <div class="flex flex-col items-center gap-6 text-center">
            <div class="flex items-center gap-2.5">
                <div class="w-6 h-6 rounded-sm bg-brand-600 flex items-center justify-center">
                    <span class="text-white text-base leading-none translate-y-px">D</span>
                </div>
                <span class="text-sm font-medium tracking-[0.24em] uppercase text-ink-700">Darejer</span>
            </div>
            <h1 class="text-3xl leading-tight text-ink-900 tracking-tight">
                {{ __('Verify email') }}
            </h1>
        </div>

        <Alert v-if="verificationSent" class="py-2.5 bg-success-50 border-success-100">
            <CheckCircle2 class="w-4 h-4 text-success-600" />
            <AlertDescription class="text-sm text-success-700">
                {{ __('A fresh verification link has been sent to your email address.') }}
            </AlertDescription>
        </Alert>

        <div class="space-y-3">
            <button
                type="button"
                class="group w-full inline-flex items-center justify-center gap-2 h-10 px-4 rounded-sm
                       bg-brand-600 hover:bg-brand-700 text-paper-50 text-sm font-medium tracking-wide
                       border border-transparent transition-colors disabled:opacity-60"
                :disabled="form.processing"
                @click="send"
            >
                {{ __('Resend verification email') }}
                <ArrowRight class="w-3.5 h-3.5 transition-transform group-hover:translate-x-0.5" />
            </button>

            <button
                type="button"
                class="text-sm text-ink-500 hover:text-brand-600 transition-colors"
                @click="logout"
            >
                {{ __('Sign out') }}
            </button>
        </div>

        <div class="flex items-center justify-between *:text-ink-400  tabular-nums pt-4 border-t border-paper-200">
            <p>Darejer</p>
            <p>{{ new Date().getFullYear() }}</p>
        </div>
    </div>
</template>
