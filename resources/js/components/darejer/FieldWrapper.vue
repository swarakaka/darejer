<script setup lang="ts">
import { HelpCircle, AlertCircle } from 'lucide-vue-next'
import { computed, type HTMLAttributes, watch } from 'vue'
import { Label } from '@/components/ui/label'
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip'
import { evaluateDependOn } from '@/composables/useDependOn'
import { cn } from '@/lib/utils'
import type { DarejerComponent } from '@/types/darejer'

const props = defineProps<{
  component: DarejerComponent
  record: Record<string, unknown>
  errors: Record<string, string>
  formData?: Record<string, unknown>
  class?: HTMLAttributes['class']
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

// Errors arrive flat with dotted keys (Inertia/Laravel convention). For
// nested-shape fields like translatable (`name.en`) or array-shape fields,
// the parent key (`name`) is never set — so a literal `errors[name]` lookup
// would render nothing. Fall back to the first matching `{name}.*` so the
// inline error message surfaces regardless of how the rule keyed it.
const fieldError = computed(() => {
  const direct = props.errors[props.component.name]
  if (direct) return direct

  const prefix = `${props.component.name}.`
  for (const key in props.errors) {
    if (key.startsWith(prefix)) return props.errors[key]
  }
  return null
})
const hasError = computed(() => !!fieldError.value)
</script>

<template>
  <div v-if="isVisible" :class="cn('flex flex-col gap-1.5', props.class, { 'col-span-full': component.fullWidth })">
    <!-- Label row -->
    <div v-if="component.label" class="flex min-h-3.5 items-center gap-1.5">
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
              <HelpCircle class="h-3 w-3" />
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
    <p v-if="hasError" class="text-danger-600 flex items-start gap-1 text-xs leading-snug">
      <AlertCircle class="mt-[2px] h-3 w-3 shrink-0" />
      <span>{{ fieldError }}</span>
    </p>

    <!-- Hint -->
    <p v-else-if="component.hint" class="text-ink-400 text-xs leading-snug">
      {{ component.hint }}
    </p>
  </div>
</template>
