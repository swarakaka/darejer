<script setup lang="ts">
import { useForm }          from '@inertiajs/vue3'
import AuthLayout           from '@/layouts/AuthLayout.vue'
import { Input }            from '@/components/ui/input'
import { Label }            from '@/components/ui/label'
import { Checkbox }         from '@/components/ui/checkbox'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { AlertCircle, ArrowRight } from 'lucide-vue-next'

defineOptions({ layout: AuthLayout })

const form = useForm({
    email:    '',
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
    <div class="space-y-8">

        <div>
            <div class="text-[10px] font-semibold uppercase tracking-[0.18em] text-brand-600 mb-2">Entry · Account</div>
            <h1 class="font-serif text-3xl leading-tight text-ink-900 tracking-tight">
                Sign in to the <em class="italic text-brand-600">Ledger</em>.
            </h1>
            <p class="text-sm text-ink-500 mt-2">
                Records are kept continuously. Your session resumes where it was last balanced.
            </p>
        </div>

        <Alert v-if="form.errors.email" variant="destructive" class="py-2.5 bg-danger-50 border-danger-100">
            <AlertCircle class="w-4 h-4 text-danger-600" />
            <AlertDescription class="text-sm text-danger-700">{{ form.errors.email }}</AlertDescription>
        </Alert>

        <form class="space-y-5" @submit.prevent="submit">

            <div class="flex flex-col gap-1.5">
                <Label for="email">Email</Label>
                <Input
                    id="email"
                    v-model="form.email"
                    type="email"
                    autocomplete="email"
                    placeholder="you@company.com"
                    :class="{ 'border-danger-600': form.errors.email }"
                />
            </div>

            <div class="flex flex-col gap-1.5">
                <div class="flex items-center justify-between">
                    <Label for="password">Password</Label>
                    <a
                        :href="route('password.request').toString()"
                        class="text-xs text-ink-500 hover:text-brand-600 transition-colors"
                    >
                        Forgot password?
                    </a>
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
                    Keep me signed in
                </Label>
            </div>

            <button
                type="submit"
                class="group w-full inline-flex items-center justify-center gap-2 h-10 px-4 rounded-sm
                       bg-ink-900 hover:bg-ink-800 text-paper-50 text-sm font-medium tracking-wide
                       border border-transparent transition-colors disabled:opacity-60"
                :disabled="form.processing"
            >
                {{ form.processing ? 'Reconciling…' : 'Sign in' }}
                <ArrowRight class="w-3.5 h-3.5 transition-transform group-hover:translate-x-0.5" />
            </button>

        </form>

        <p class="text-xs text-ink-400 tabular-nums pt-4 border-t border-paper-200">
            Protected by session encryption · Session expires after inactivity
        </p>
    </div>
</template>
