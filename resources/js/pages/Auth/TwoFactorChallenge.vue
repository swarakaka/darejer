<script setup lang="ts">
import { ref, computed } from 'vue'
import { useForm }       from '@inertiajs/vue3'
import AuthLayout        from '@/layouts/AuthLayout.vue'
import { Input }         from '@/components/ui/input'
import { Label }         from '@/components/ui/label'
import { ArrowRight }    from 'lucide-vue-next'

defineOptions({ layout: AuthLayout })

const recovery = ref(false)

const form = useForm({
    code:          '',
    recovery_code: '',
})

const heading = computed(() =>
    recovery.value ? 'Enter a recovery code' : 'Two-factor authentication'
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
    <div class="space-y-8">

        <div>
            <div class="text-[10px] font-semibold uppercase tracking-[0.18em] text-brand-600 mb-2">Entry · Two Factor</div>
            <h1 class="font-serif text-3xl leading-tight text-ink-900 tracking-tight">
                {{ heading }}.
            </h1>
            <p class="text-sm text-ink-500 mt-2">
                <template v-if="recovery">
                    Please confirm access to your account by entering one of your emergency recovery codes.
                </template>
                <template v-else>
                    Please confirm access to your account by entering the code from your authenticator application.
                </template>
            </p>
        </div>

        <form class="space-y-5" @submit.prevent="submit">

            <div v-if="!recovery" class="flex flex-col gap-1.5">
                <Label for="code">Authentication code</Label>
                <Input
                    id="code"
                    v-model="form.code"
                    type="text"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    placeholder="123456"
                    :class="{ 'border-danger-600': form.errors.code }"
                />
                <p v-if="form.errors.code" class="text-xs text-danger-600">{{ form.errors.code }}</p>
            </div>

            <div v-else class="flex flex-col gap-1.5">
                <Label for="recovery_code">Recovery code</Label>
                <Input
                    id="recovery_code"
                    v-model="form.recovery_code"
                    type="text"
                    autocomplete="one-time-code"
                    :class="{ 'border-danger-600': form.errors.recovery_code }"
                />
                <p v-if="form.errors.recovery_code" class="text-xs text-danger-600">{{ form.errors.recovery_code }}</p>
            </div>

            <button
                type="submit"
                class="group w-full inline-flex items-center justify-center gap-2 h-10 px-4 rounded-sm
                       bg-ink-900 hover:bg-ink-800 text-paper-50 text-sm font-medium tracking-wide
                       border border-transparent transition-colors disabled:opacity-60"
                :disabled="form.processing"
            >
                {{ form.processing ? 'Verifying…' : 'Continue' }}
                <ArrowRight class="w-3.5 h-3.5 transition-transform group-hover:translate-x-0.5" />
            </button>

            <button
                type="button"
                class="text-sm text-ink-500 hover:text-brand-600 transition-colors"
                @click="toggle"
            >
                {{ recovery ? 'Use an authentication code' : 'Use a recovery code' }}
            </button>

        </form>
    </div>
</template>
