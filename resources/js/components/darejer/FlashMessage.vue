<script setup lang="ts">
import { computed, watch } from 'vue'
import { usePage }         from '@inertiajs/vue3'
import { toast }           from 'vue-sonner'
import { Toaster }         from '@/components/ui/sonner'
import type { DarejerSharedProps, FlashProps } from '@/types/darejer'

// Inertia v3 emits flash data at the top-level page key (`page.flash`) —
// not under shared props. Controllers populate it via `Inertia::flash()`,
// which the Inertia middleware pulls from session and serializes onto the
// next page response, then auto-clears. shadcn-vue's Toast primitive is
// deprecated, so we render via Sonner (vue-sonner).
const page = usePage<DarejerSharedProps>()

const direction = computed(() => page.props.darejer?.direction ?? 'ltr')

// Toast tray sits in the inline-end corner regardless of language direction.
const toasterPosition = computed<'top-right' | 'top-left'>(() =>
    direction.value === 'rtl' ? 'top-left' : 'top-right'
)

watch(
    () => (page as unknown as { flash?: FlashProps | null }).flash,
    (flash) => {
        if (!flash) return
        if (flash.success) toast.success(flash.success)
        if (flash.error)   toast.error(flash.error)
        if (flash.warning) toast.warning(flash.warning)
        if (flash.info)    toast.info(flash.info)
    },
    { deep: true, immediate: true }
)
</script>

<template>
    <Toaster :position="toasterPosition" rich-colors close-button />
</template>
