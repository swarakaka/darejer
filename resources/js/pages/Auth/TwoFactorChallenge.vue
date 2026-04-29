<script setup lang="ts">
import { ref, computed } from 'vue'
import { useForm }       from '@inertiajs/vue3'
import AuthLayout        from '@/layouts/AuthLayout.vue'
import { Input }         from '@/components/ui/input'
import { Label }         from '@/components/ui/label'
import { ArrowRight, AlertCircle, ShieldCheck } from 'lucide-vue-next'
import useTranslation    from '@/composables/useTranslation'

defineOptions({ layout: AuthLayout })

const { __ } = useTranslation()

const recovery = ref(false)

const form = useForm({
    code:          '',
    recovery_code: '',
})

const heading = computed(() =>
    recovery.value ? __('Enter recovery code') : __('Two-factor verification')
)

const subhead = computed(() =>
    recovery.value
        ? __('Use one of your saved recovery codes.')
        : __('Open your authenticator app and enter the 6-digit code.')
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
                <div class="w-7 h-7 rounded-md bg-brand-600 flex items-center justify-center ring-1 ring-brand-400/30 shadow-sm">
                    <span class="text-white text-base font-semibold leading-none">D</span>
                </div>
                <span class="text-xs font-semibold tracking-[0.28em] uppercase text-ink-700">Darejer</span>
            </div>
            <span class="inline-flex items-center gap-1 text-2xs font-semibold uppercase tracking-[0.24em] text-brand-700">
                <ShieldCheck class="w-3 h-3" />
                {{ __('Two-factor') }}
            </span>
            <h1 class="text-3xl font-semibold leading-tight text-ink-900 tracking-tight">
                {{ heading }}
            </h1>
            <p class="text-sm text-ink-500 leading-relaxed">
                {{ subhead }}
            </p>
        </div>

        <form class="space-y-5" @submit.prevent="submit">

            <div v-if="!recovery" class="flex flex-col gap-1.5">
                <Label for="code" class="text-xs font-semibold text-ink-700">{{ __('Authentication code') }}</Label>
                <Input
                    id="code"
                    v-model="form.code"
                    type="text"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    placeholder="123456"
                    class="h-11 text-center text-lg font-mono tabular-nums tracking-[0.35em]"
                    :class="{ 'border-danger-600 focus-visible:ring-danger-500/20': form.errors.code }"
                />
                <p v-if="form.errors.code" class="flex items-center gap-1 text-xs text-danger-600">
                    <AlertCircle class="w-3 h-3" />
                    {{ form.errors.code }}
                </p>
            </div>

            <div v-else class="flex flex-col gap-1.5">
                <Label for="recovery_code" class="text-xs font-semibold text-ink-700">{{ __('Recovery code') }}</Label>
                <Input
                    id="recovery_code"
                    v-model="form.recovery_code"
                    type="text"
                    autocomplete="one-time-code"
                    class="h-10 font-mono"
                    :class="{ 'border-danger-600 focus-visible:ring-danger-500/20': form.errors.recovery_code }"
                />
                <p v-if="form.errors.recovery_code" class="flex items-center gap-1 text-xs text-danger-600">
                    <AlertCircle class="w-3 h-3" />
                    {{ form.errors.recovery_code }}
                </p>
            </div>

            <button
                type="submit"
                class="group w-full inline-flex items-center justify-center gap-2 h-11 px-4 rounded-md
                       bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold tracking-wide
                       shadow-sm transition-colors disabled:opacity-60 disabled:cursor-not-allowed"
                :disabled="form.processing"
            >
                {{ form.processing ? __('Verifying') : __('Continue') }}
                <ArrowRight class="w-4 h-4 transition-transform group-hover:translate-x-0.5 rtl:rotate-180 rtl:group-hover:-translate-x-0.5" />
            </button>

            <button
                type="button"
                class="text-sm font-medium text-ink-500 hover:text-brand-700 transition-colors"
                @click="toggle"
            >
                {{ recovery ? __('Use an authentication code') : __('Use a recovery code') }}
            </button>

        </form>

        <div class="flex items-center justify-between text-2xs uppercase tracking-[0.18em] text-ink-400 tabular-nums pt-5 border-t border-paper-200">
            <p>{{ __('© :year Darejer', { year: new Date().getFullYear() }) }}</p>
            <p>{{ __('Secured with TLS') }}</p>
        </div>
    </div>
</template>
