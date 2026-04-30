<script setup lang="ts">
import type { TabsRootEmits, TabsRootProps } from "reka-ui"
import { reactiveOmit } from "@vueuse/core"
import { TabsRoot, useForwardPropsEmits } from "reka-ui"
import { computed } from "vue"

const props = defineProps<TabsRootProps & { persistKey?: string }>()
const emits = defineEmits<TabsRootEmits>()

const storageKey = computed(() =>
  props.persistKey ? `darejer:tabs:${props.persistKey}` : null,
)

const initialDefaultValue = computed<string | number | undefined>(() => {
  if (storageKey.value && typeof window !== "undefined") {
    try {
      const stored = window.localStorage.getItem(storageKey.value)
      if (stored !== null) {
        return stored
      }
    } catch {
      // localStorage unavailable (private mode / quota) — fall back silently.
    }
  }
  return props.defaultValue
})

const delegated = reactiveOmit(props, "persistKey", "defaultValue")
const forwarded = useForwardPropsEmits(delegated, emits)

function handleUpdate(value: string | number) {
  if (storageKey.value && typeof window !== "undefined") {
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
