<script setup lang="ts">
import { computed } from 'vue'
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip'
import { HelpCircle } from 'lucide-vue-next'
import type { DarejerComponent } from '@/types/darejer'

const props = defineProps<{
    component: DarejerComponent
    record:    Record<string, unknown>
    errors:    Record<string, string>
    formData?: Record<string, unknown>
}>()

const triggerType = computed(() => (props.component.triggerType as string) ?? 'text')
const content     = computed(() => (props.component.content     as string) ?? '')
const trigger     = computed(() => (props.component.trigger     as string) ?? '')
const side        = computed(() => (props.component.side        as 'top' | 'bottom' | 'left' | 'right') ?? 'top')
</script>

<template>
    <div class="inline-flex items-center">
        <TooltipProvider :delay-duration="100">
            <Tooltip>
                <TooltipTrigger as-child>
                    <span
                        v-if="triggerType === 'text'"
                        class="text-sm text-ink-700 cursor-help underline decoration-dotted decoration-ink-400 underline-offset-2"
                    >
                        {{ trigger }}
                    </span>
                    <button
                        v-else
                        type="button"
                        class="inline-flex items-center justify-center text-ink-400 hover:text-ink-600 transition-colors"
                    >
                        <HelpCircle class="w-4 h-4" />
                    </button>
                </TooltipTrigger>
                <TooltipContent :side="side" class="max-w-xs text-xs leading-relaxed">
                    {{ content }}
                </TooltipContent>
            </Tooltip>
        </TooltipProvider>
    </div>
</template>
