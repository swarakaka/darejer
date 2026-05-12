<script setup lang="ts">
import type { TabsRootEmits, TabsRootProps } from 'reka-ui'
import { reactiveOmit } from '@vueuse/core'
import { TabsRoot, useForwardPropsEmits } from 'reka-ui'
import { computed } from 'vue'

const props = defineProps<
  TabsRootProps & { persistKey?: string; validValues?: (string | number)[] }
>()
const emits = defineEmits<TabsRootEmits>()

const storageKey = computed(() => (props.persistKey ? `darejer:tabs:${props.persistKey}` : null))

// The persistKey is intentionally shared across related screens (e.g. show vs.
// create/edit of the same resource), but those screens may expose different
// tab sets. If the stored value doesn't correspond to any current trigger,
// TabsRoot ends up with no active tab — so validate against `validValues`
// before honoring the persisted choice.
const initialDefaultValue = computed<string | number | undefined>(() => {
  if (storageKey.value && typeof window !== 'undefined') {
    try {
      const stored = window.localStorage.getItem(storageKey.value)
      if (stored !== null && (!props.validValues || props.validValues.map(String).includes(stored))) {
        return stored
      }
    } catch {
      // localStorage unavailable (private mode / quota) — fall back silently.
    }
  }
  return props.defaultValue
})

const delegated = reactiveOmit(props, 'persistKey', 'defaultValue', 'validValues')
const forwarded = useForwardPropsEmits(delegated, emits)

function handleUpdate(value: string | number) {
  if (storageKey.value && typeof window !== 'undefined') {
    try {
      window.localStorage.setItem(storageKey.value, String(value))
    } catch {
      // ignore write failures
    }
  }
}
</script>

<template>
  <TabsRoot
    v-bind="forwarded"
    :default-value="initialDefaultValue"
    @update:model-value="handleUpdate"
  >
    <slot />
  </TabsRoot>
</template>
