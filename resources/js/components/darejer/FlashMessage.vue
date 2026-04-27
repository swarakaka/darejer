<script setup lang="ts">
import { watch }       from 'vue'
import { usePage }     from '@inertiajs/vue3'
import { toast }       from 'vue-sonner'
import { Toaster }     from '@/components/ui/sonner'
import type { DarejerSharedProps } from '@/types/darejer'

// Flash messages come from Laravel session via Inertia shared props.
// shadcn-vue's Toast primitive is deprecated; we use Sonner (vue-sonner) instead.
const page = usePage<DarejerSharedProps>()

watch(
    () => page.props.flash,
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
    <Toaster position="top-right" rich-colors close-button />
</template>
