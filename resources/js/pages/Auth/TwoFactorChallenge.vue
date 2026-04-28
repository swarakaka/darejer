<script setup lang="ts">
import { ref, computed } from 'vue'
import { useForm }       from '@inertiajs/vue3'
import AuthLayout        from '@/layouts/AuthLayout.vue'
import { Input }         from '@/components/ui/input'
import { Label }         from '@/components/ui/label'
import { ArrowRight }    from 'lucide-vue-next'
import useTranslation    from '@/composables/useTranslation'

defineOptions({ layout: AuthLayout })

const { __ } = useTranslation()

const recovery = ref(false)

const form = useForm({
    code:          '',
    recovery_code: '',
})

const heading = computed(() =>
    recovery.value ? __('Recovery code') : __('Two-factor')
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
    <div class="space-y-8 p-6 bg-white">

        <div class="flex flex-col items-center gap-6 text-center">
            <div class="flex items-center gap-2.5">
                <div class="w-6 h-6 rounded-sm bg-brand-600 flex items-center justify-center">
                    <span class="font-serif text-white text-base leading-none translate-y-[1px]">D</span>
                </div>
                <span class="text-sm font-medium tracking-[0.24em] uppercase text-ink-700">Darejer</span>
            </div>
            <h1 class="font-serif text-3xl leading-tight text-ink-900 tracking-tight">
                {{ heading }}
            </h1>
        </div>

        <form class="space-y-5" @submit.prevent="submit">

            <div v-if="!recovery" class="flex flex-col gap-1.5">
                <Label for="code">{{ __('Authentication code') }}</Label>
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
                <Label for="recovery_code">{{ __('Recovery code') }}</Label>
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
                       bg-brand-600 hover:bg-brand-700 text-paper-50 text-sm font-medium tracking-wide
                       border border-transparent transition-colors disabled:opacity-60"
                :disabled="form.processing"
            >
                {{ form.processing ? __('Verifying') : __('Continue') }}
                <ArrowRight class="w-3.5 h-3.5 transition-transform group-hover:translate-x-0.5" />
            </button>

            <button
                type="button"
                class="text-sm text-ink-500 hover:text-brand-600 transition-colors"
                @click="toggle"
            >
                {{ recovery ? __('Use an authentication code') : __('Use a recovery code') }}
            </button>

        </form>

        <div class="flex items-center justify-between *:text-ink-400  tabular-nums pt-4 border-t border-paper-200">
            <p>Darejer</p>
            <p>{{ new Date().getFullYear() }}</p>
        </div>
    </div>
</template>
