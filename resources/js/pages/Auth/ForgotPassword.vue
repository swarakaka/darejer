<script setup lang="ts">
import { useForm, Link }    from '@inertiajs/vue3'
import AuthLayout           from '@/layouts/AuthLayout.vue'
import { Input }            from '@/components/ui/input'
import { Label }            from '@/components/ui/label'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { CheckCircle2, ArrowLeft } from 'lucide-vue-next'

defineOptions({ layout: AuthLayout })

defineProps<{ status?: string }>()

const form = useForm({ email: '' })

function submit() {
    form.post(route('password.email').toString())
}
</script>

<template>
    <div class="space-y-8">

        <div>
            <div class="text-[10px] font-semibold uppercase tracking-[0.18em] text-brand-600 mb-2">Entry · Recovery</div>
            <h1 class="font-serif text-3xl leading-tight text-ink-900 tracking-tight">
                Reset your <em class="italic text-brand-600">password</em>.
            </h1>
            <p class="text-sm text-ink-500 mt-2">
                Enter the email on file and we'll issue a recovery link.
            </p>
        </div>

        <Alert v-if="status" class="py-2.5 bg-success-50 border-success-100">
            <CheckCircle2 class="w-4 h-4 text-success-600" />
            <AlertDescription class="text-sm text-success-700">{{ status }}</AlertDescription>
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
                <p v-if="form.errors.email" class="text-xs text-danger-600">{{ form.errors.email }}</p>
            </div>

            <button
                type="submit"
                class="w-full inline-flex items-center justify-center h-10 px-4 rounded-sm
                       bg-ink-900 hover:bg-ink-800 text-paper-50 text-sm font-medium tracking-wide
                       border border-transparent transition-colors disabled:opacity-60"
                :disabled="form.processing"
            >
                {{ form.processing ? 'Sending…' : 'Send reset link' }}
            </button>
        </form>

        <Link
            :href="route('login').toString()"
            class="inline-flex items-center gap-1.5 text-sm text-ink-500 hover:text-brand-600 transition-colors"
        >
            <ArrowLeft class="w-3.5 h-3.5" />
            Back to sign in
        </Link>
    </div>
</template>
