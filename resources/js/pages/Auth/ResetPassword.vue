<script setup lang="ts">
import { useForm }  from '@inertiajs/vue3'
import AuthLayout   from '@/layouts/AuthLayout.vue'
import { Input }    from '@/components/ui/input'
import { Label }    from '@/components/ui/label'

defineOptions({ layout: AuthLayout })

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
    <div class="space-y-8">

        <div>
            <div class="text-[10px] font-semibold uppercase tracking-[0.18em] text-brand-600 mb-2">Entry · Recovery</div>
            <h1 class="font-serif text-3xl leading-tight text-ink-900 tracking-tight">
                Choose a <em class="italic text-brand-600">new password</em>.
            </h1>
            <p class="text-sm text-ink-500 mt-2">
                Make it memorable — but not guessable.
            </p>
        </div>

        <form class="space-y-5" @submit.prevent="submit">

            <div class="flex flex-col gap-1.5">
                <Label for="email">Email</Label>
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
                <Label for="password">New password</Label>
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
                <Label for="password_confirmation">Confirm password</Label>
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
                class="w-full inline-flex items-center justify-center h-10 px-4 rounded-sm
                       bg-ink-900 hover:bg-ink-800 text-paper-50 text-sm font-medium tracking-wide
                       border border-transparent transition-colors disabled:opacity-60"
                :disabled="form.processing"
            >
                {{ form.processing ? 'Saving…' : 'Save new password' }}
            </button>

        </form>
    </div>
</template>
