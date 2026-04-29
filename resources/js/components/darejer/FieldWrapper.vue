<script setup lang="ts">
import { computed, watch } from 'vue'
import { Label }           from '@/components/ui/label'
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip'
import { HelpCircle, AlertCircle } from 'lucide-vue-next'
import { evaluateDependOn } from '@/composables/useDependOn'
import type { DarejerComponent } from '@/types/darejer'

const props = defineProps<{
    component:  DarejerComponent
    record:     Record<string, unknown>
    errors:     Record<string, string>
    formData?:  Record<string, unknown>
}>()

const emit = defineEmits<{
    (e: 'hidden', name: string): void
}>()

const isVisible = computed(() => {
    const source = props.formData ?? props.record
    return evaluateDependOn(props.component.dependOn, source)
})

// When a field transitions from visible → hidden, notify the parent so it can
// clear the value (cascading reset).
watch(isVisible, (visible, wasVisible) => {
    if (!visible && wasVisible) {
        emit('hidden', props.component.name)
    }
})

const fieldError = computed(() => props.errors[props.component.name] ?? null)
const hasError   = computed(() => !!fieldError.value)
</script>

<template>
    <div
        v-if="isVisible"
        class="flex flex-col gap-1.5"
        :class="{ 'col-span-full': component.fullWidth }"
    >
        <!-- Label row -->
        <div v-if="component.label" class="flex items-center gap-1.5 min-h-[14px]">
            <Label
                :for="component.name"
                class="flex items-center gap-1 text-xs font-semibold tracking-tight"
                :class="hasError ? 'text-danger-600' : 'text-ink-700'"
            >
                <span>{{ component.label }}</span>
                <span v-if="component.required" class="text-danger-600 leading-none">*</span>
            </Label>

            <TooltipProvider v-if="component.tooltip" :delay-duration="0">
                <Tooltip>
                    <TooltipTrigger as-child>
                        <button type="button" class="text-ink-300 hover:text-ink-600 transition-colors">
                            <HelpCircle class="w-3 h-3" />
                        </button>
                    </TooltipTrigger>
                    <TooltipContent class="max-w-xs text-xs leading-relaxed">
                        {{ component.tooltip }}
                    </TooltipContent>
                </Tooltip>
            </TooltipProvider>
        </div>

        <!-- Field slot -->
        <slot :error="fieldError" :has-error="hasError" />

        <!-- Error -->
        <p
            v-if="hasError"
            class="flex items-start gap-1 text-xs text-danger-600 leading-snug"
        >
            <AlertCircle class="w-3 h-3 shrink-0 mt-[2px]" />
            <span>{{ fieldError }}</span>
        </p>

        <!-- Hint -->
        <p
            v-else-if="component.hint"
            class="text-xs text-ink-400 leading-snug"
        >
            {{ component.hint }}
        </p>
    </div>
</template>
