<script setup lang="ts">
import { useForm }    from '@inertiajs/vue3'
import AuthLayout     from '@/layouts/AuthLayout.vue'
import { Input }      from '@/components/ui/input'
import { Label }      from '@/components/ui/label'
import { ArrowRight } from 'lucide-vue-next'
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
    <div class="space-y-8">

        <div>
            <div class="text-[10px] font-semibold uppercase tracking-[0.18em] text-brand-600 mb-2">{{ __('Entry · Confirm') }}</div>
            <h1 class="font-serif text-3xl leading-tight text-ink-900 tracking-tight">
                {{ __('Confirm your') }} <em class="italic text-brand-600">{{ __('password') }}</em>.
            </h1>
            <p class="text-sm text-ink-500 mt-2">
                {{ __('This is a secure area. Please re-enter your password to continue.') }}
            </p>
        </div>

        <form class="space-y-5" @submit.prevent="submit">
            <div class="flex flex-col gap-1.5">
                <Label for="password">{{ __('Password') }}</Label>
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

            <button
                type="submit"
                class="group w-full inline-flex items-center justify-center gap-2 h-10 px-4 rounded-sm
                       bg-ink-900 hover:bg-ink-800 text-paper-50 text-sm font-medium tracking-wide
                       border border-transparent transition-colors disabled:opacity-60"
                :disabled="form.processing"
            >
                {{ form.processing ? __('Confirming') : __('Confirm') }}
                <ArrowRight class="w-3.5 h-3.5 transition-transform group-hover:translate-x-0.5" />
            </button>
        </form>
    </div>
</template>
