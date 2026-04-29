<script setup lang="ts">
import { useForm }    from '@inertiajs/vue3'
import AuthLayout     from '@/layouts/AuthLayout.vue'
import { Input }      from '@/components/ui/input'
import { Label }      from '@/components/ui/label'
import { ArrowRight, AlertCircle, ShieldCheck } from 'lucide-vue-next'
import useTranslation from '@/composables/useTranslation'

defineOptions({ layout: AuthLayout })

const { __ } = useTranslation()

const form = useForm({ password: '' })

function submit() {
    form.post(route('password.confirm').toString(), {
        onFinish: () => form.reset('password'),
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
                {{ __('Secure area') }}
            </span>
            <h1 class="text-3xl font-semibold leading-tight text-ink-900 tracking-tight">
                {{ __('Confirm your password') }}
            </h1>
            <p class="text-sm text-ink-500 leading-relaxed">
                {{ __('Re-enter your password to continue with this sensitive action.') }}
            </p>
        </div>

        <form class="space-y-5" @submit.prevent="submit">

            <div class="flex flex-col gap-1.5">
                <Label for="password" class="text-xs font-semibold text-ink-700">{{ __('Password') }}</Label>
                <Input
                    id="password"
                    v-model="form.password"
                    type="password"
                    autocomplete="current-password"
                    placeholder="••••••••"
                    class="h-10"
                    :class="{ 'border-danger-600 focus-visible:ring-danger-500/20': form.errors.password }"
                />
                <p v-if="form.errors.password" class="flex items-center gap-1 text-xs text-danger-600">
                    <AlertCircle class="w-3 h-3" />
                    {{ form.errors.password }}
                </p>
            </div>

            <button
                type="submit"
                class="group w-full inline-flex items-center justify-center gap-2 h-11 px-4 rounded-md
                       bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold tracking-wide
                       shadow-sm transition-colors disabled:opacity-60 disabled:cursor-not-allowed"
                :disabled="form.processing"
            >
                {{ form.processing ? __('Confirming') : __('Confirm') }}
                <ArrowRight class="w-4 h-4 transition-transform group-hover:translate-x-0.5 rtl:rotate-180 rtl:group-hover:-translate-x-0.5" />
            </button>

        </form>

        <div class="flex items-center justify-between text-2xs uppercase tracking-[0.18em] text-ink-400 tabular-nums pt-5 border-t border-paper-200">
            <p>{{ __('© :year Darejer', { year: new Date().getFullYear() }) }}</p>
            <p>{{ __('Secured with TLS') }}</p>
        </div>
    </div>
</template>
