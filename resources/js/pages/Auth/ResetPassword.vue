<script setup lang="ts">
import { useForm }    from '@inertiajs/vue3'
import AuthLayout     from '@/layouts/AuthLayout.vue'
import { Input }      from '@/components/ui/input'
import { Label }      from '@/components/ui/label'
import { ArrowRight } from 'lucide-vue-next'
import useTranslation from '@/composables/useTranslation'

defineOptions({ layout: AuthLayout })

const { __ } = useTranslation()

const props = defineProps<{ token: string; email: string }>()

const form = useForm({
    token:                 props.token,
    email:                 props.email,
    password:              '',
    password_confirmation: '',
})

function submit() {
    form.post(route('password.update').toString(), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    })
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
                {{ __('Reset password') }}
            </h1>
        </div>

        <form class="space-y-5" @submit.prevent="submit">

            <div class="flex flex-col gap-1.5">
                <Label for="email">{{ __('Email') }}</Label>
                <Input
                    id="email"
                    v-model="form.email"
                    type="email"
                    readonly
                    class="bg-paper-100 text-ink-500 tabular-nums"
                />
                <p v-if="form.errors.email" class="text-xs text-danger-600">{{ form.errors.email }}</p>
            </div>

            <div class="flex flex-col gap-1.5">
                <Label for="password">{{ __('New password') }}</Label>
                <Input
                    id="password"
                    v-model="form.password"
                    type="password"
                    autocomplete="new-password"
                    placeholder="••••••••"
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
                    placeholder="••••••••"
                    :class="{ 'border-danger-600': form.errors.password_confirmation }"
                />
                <p v-if="form.errors.password_confirmation" class="text-xs text-danger-600">
                    {{ form.errors.password_confirmation }}
                </p>
            </div>

            <button
                type="submit"
                class="group w-full inline-flex items-center justify-center gap-2 h-10 px-4 rounded-sm
                       bg-brand-600 hover:bg-brand-700 text-paper-50 text-sm font-medium tracking-wide
                       border border-transparent transition-colors disabled:opacity-60"
                :disabled="form.processing"
            >
                {{ form.processing ? __('Saving') : __('Save new password') }}
                <ArrowRight class="w-3.5 h-3.5 transition-transform group-hover:translate-x-0.5" />
            </button>

        </form>

        <div class="flex items-center justify-between *:text-ink-400  tabular-nums pt-4 border-t border-paper-200">
            <p>Darejer</p>
            <p>{{ new Date().getFullYear() }}</p>
        </div>
    </div>
</template>
