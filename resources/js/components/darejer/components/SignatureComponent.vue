<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import SignaturePad from 'signature_pad'
import { Eraser, Download } from 'lucide-vue-next'
import FieldWrapper from '@/components/darejer/FieldWrapper.vue'
import useTranslation from '@/composables/useTranslation'
import type { DarejerComponent } from '@/types/darejer'

const { __ } = useTranslation()

const props = defineProps<{
    component: DarejerComponent
    record:    Record<string, unknown>
    errors:    Record<string, string>
    formData?: Record<string, unknown>
}>()

const emit = defineEmits<{ (e: 'update', name: string, value: unknown): void }>()

const canvasEl = ref<HTMLCanvasElement | null>(null)
let   pad: SignaturePad | null = null

const height    = computed(() => (props.component.height   as number) ?? 160)
const penColor  = computed(() => (props.component.penColor as string) ?? '#000000')
const bgColor   = computed(() => (props.component.bgColor  as string) ?? '#ffffff')
const showGuide = computed(() => props.component.showGuide !== false)

const rawValue = (props.formData ?? props.record)[props.component.name]
    ?? props.component.default ?? null

const hasSignature = ref(!!rawValue)

function resizeCanvas() {
    if (!canvasEl.value || !pad) return
    const ratio  = Math.max(window.devicePixelRatio || 1, 1)
    const canvas = canvasEl.value
    const data   = pad.toData()
    canvas.width  = canvas.offsetWidth  * ratio
    canvas.height = canvas.offsetHeight * ratio
    const ctx = canvas.getContext('2d')
    if (ctx) {
        ctx.scale(ratio, ratio)
        ctx.fillStyle = bgColor.value
        ctx.fillRect(0, 0, canvas.width, canvas.height)
    }
    pad.clear()
    pad.fromData(data)
}

onMounted(() => {
    if (!canvasEl.value) return

    pad = new SignaturePad(canvasEl.value, {
        penColor:        penColor.value,
        backgroundColor: bgColor.value,
    })

    if (rawValue && typeof rawValue === 'string' && rawValue.startsWith('data:')) {
        pad.fromDataURL(rawValue)
        hasSignature.value = true
    }

    if (props.component.disabled) {
        pad.off()
    }

    pad.addEventListener('endStroke', () => {
        hasSignature.value = !pad!.isEmpty()
        emit('update', props.component.name, pad!.isEmpty() ? null : pad!.toDataURL('image/png'))
    })

    resizeCanvas()
    window.addEventListener('resize', resizeCanvas)
})

onBeforeUnmount(() => {
    window.removeEventListener('resize', resizeCanvas)
    pad?.off()
})

function clear() {
    if (!pad || props.component.disabled) return
    pad.clear()
    const canvas = canvasEl.value
    if (canvas) {
        const ctx = canvas.getContext('2d')
        if (ctx) {
            ctx.fillStyle = bgColor.value
            ctx.fillRect(0, 0, canvas.width, canvas.height)
        }
    }
    hasSignature.value = false
    emit('update', props.component.name, null)
}

function download() {
    if (!pad || pad.isEmpty()) return
    const link = document.createElement('a')
    link.download = `${props.component.name}-signature.png`
    link.href = pad.toDataURL('image/png')
    link.click()
}
</script>

<template>
    <FieldWrapper
        :component="component"
        :record="record"
        :errors="errors"
        :form-data="formData"
        class="col-span-full"
    >
        <template #default="{ hasError }">
            <div
                class="border rounded-md overflow-hidden"
                :class="hasError ? 'border-danger-600' : 'border-paper-300'"
            >
                <!-- Canvas -->
                <div class="relative bg-white" :style="{ height: height + 'px' }">

                    <canvas
                        ref="canvasEl"
                        class="w-full h-full block"
                        :class="(component.disabled as boolean) ? 'cursor-not-allowed' : 'cursor-crosshair'"
                    />

                    <!-- "Sign here" guide line -->
                    <div
                        v-if="showGuide && !hasSignature"
                        class="absolute bottom-8 left-6 right-6 pointer-events-none"
                    >
                        <div class="border-b border-dashed border-paper-300" />
                        <p class="text-xs text-ink-300 mt-1 tracking-wider uppercase">{{ __('Sign here') }}</p>
                    </div>

                    <!-- Empty state hint -->
                    <div
                        v-if="!hasSignature && !showGuide"
                        class="absolute inset-0 flex items-center justify-center pointer-events-none"
                    >
                        <p class="text-sm text-ink-300">{{ __('Draw your signature') }}</p>
                    </div>
                </div>

                <!-- Footer controls -->
                <div class="flex items-center justify-between px-3 py-1.5 bg-paper-75 border-t border-paper-200">
                    <p class="text-xs text-ink-400">
                        {{ hasSignature ? __('Signature captured') : __('No signature yet') }}
                    </p>
                    <div class="flex items-center gap-1">
                        <button
                            v-if="hasSignature"
                            type="button"
                            class="flex items-center gap-1 h-6 px-2 text-xs text-ink-500
                                   hover:text-brand-600 hover:bg-paper-100 rounded-sm transition-colors"
                            @click="download"
                        >
                            <Download class="w-3 h-3" />
                            {{ __('Save') }}
                        </button>
                        <button
                            v-if="!(component.disabled as boolean)"
                            type="button"
                            class="flex items-center gap-1 h-6 px-2 text-xs text-ink-500
                                   hover:text-danger-600 hover:bg-paper-100 rounded-sm transition-colors"
                            @click="clear"
                        >
                            <Eraser class="w-3 h-3" />
                            {{ __('Clear') }}
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </FieldWrapper>
</template>
