<script setup lang="ts">
import { ref, computed } from 'vue'
import { Download, ZoomIn, ZoomOut, RotateCw, Maximize2, FileText } from 'lucide-vue-next'
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

const pdfSrc = computed((): string | null => {
    if (props.component.src) return props.component.src as string

    if (props.component.srcField) {
        const field = props.component.srcField as string
        const value = (props.formData ?? props.record)[field]
        if (value) return String(value)
    }

    return null
})

const height      = computed(() => `${(props.component.height as number) ?? 600}px`)
const showToolbar = computed(() => props.component.showToolbar !== false)
const canDownload = computed(() => props.component.download !== false)

// Browser PDF rendering doesn't expose a JS zoom API, so we scale the iframe
// container with CSS transform and compensate its inner size so no content
// gets clipped at lower zoom levels.
const zoom    = ref(100)
const minZoom = 50
const maxZoom = 200

function zoomIn()   { zoom.value = Math.min(maxZoom, zoom.value + 10) }
function zoomOut()  { zoom.value = Math.max(minZoom, zoom.value - 10) }
function resetZoom() { zoom.value = 100 }

const containerEl  = ref<HTMLElement | null>(null)
const isFullscreen = ref(false)

function toggleFullscreen() {
    if (!containerEl.value) return
    if (!document.fullscreenElement) {
        containerEl.value.requestFullscreen()
        isFullscreen.value = true
    } else {
        document.exitFullscreen()
        isFullscreen.value = false
    }
}

function download() {
    if (!pdfSrc.value) return
    const a = document.createElement('a')
    a.href     = pdfSrc.value
    a.download = 'document.pdf'
    a.target   = '_blank'
    a.click()
}

const iframeSrc = computed(() => {
    if (!pdfSrc.value) return ''
    const sep = pdfSrc.value.includes('?') ? '&' : '#'
    return `${pdfSrc.value}${sep}toolbar=${showToolbar.value ? 1 : 0}&navpanes=0`
})
</script>

<template>
    <FieldWrapper
        :component="component"
        :record="record"
        :errors="errors"
        class="col-span-full"
    >
        <template #default>

            <!-- No PDF -->
            <div
                v-if="!pdfSrc"
                class="flex flex-col items-center justify-center gap-2 border border-dashed border-paper-300 rounded-md bg-paper-50 text-ink-400"
                :style="{ height: height }"
            >
                <FileText class="w-8 h-8" />
                <span class="text-sm">{{ __('No PDF file specified.') }}</span>
            </div>

            <!-- PDF viewer -->
            <div v-else ref="containerEl" class="flex flex-col border border-paper-200 rounded-md overflow-hidden bg-card">

                <!-- Toolbar -->
                <div class="flex items-center gap-1 px-3 py-1.5 bg-paper-75 border-b border-paper-200">

                    <button
                        type="button"
                        class="flex items-center justify-center w-7 h-7 rounded-sm text-ink-500 hover:bg-paper-200 transition-colors disabled:opacity-40"
                        :disabled="zoom <= minZoom"
                        :title="__('Zoom out')"
                        @click="zoomOut"
                    >
                        <ZoomOut class="w-3.5 h-3.5" />
                    </button>

                    <span class="text-xs text-ink-500 w-10 text-center font-mono select-none tabular-nums">
                        {{ zoom }}%
                    </span>

                    <button
                        type="button"
                        class="flex items-center justify-center w-7 h-7 rounded-sm text-ink-500 hover:bg-paper-200 transition-colors disabled:opacity-40"
                        :disabled="zoom >= maxZoom"
                        :title="__('Zoom in')"
                        @click="zoomIn"
                    >
                        <ZoomIn class="w-3.5 h-3.5" />
                    </button>

                    <button
                        type="button"
                        class="flex items-center justify-center w-7 h-7 rounded-sm text-xs text-ink-500 hover:bg-paper-200 transition-colors"
                        :title="__('Reset zoom')"
                        @click="resetZoom"
                    >
                        <RotateCw class="w-3 h-3" />
                    </button>

                    <div class="w-px h-4 bg-paper-300 mx-1" />

                    <button
                        type="button"
                        class="flex items-center justify-center w-7 h-7 rounded-sm text-ink-500 hover:bg-paper-200 transition-colors"
                        :title="__('Fullscreen')"
                        @click="toggleFullscreen"
                    >
                        <Maximize2 class="w-3.5 h-3.5" />
                    </button>

                    <button
                        v-if="canDownload"
                        type="button"
                        class="ms-auto flex items-center gap-1.5 h-7 px-2.5 text-xs text-brand-600 font-medium hover:bg-brand-50 rounded-sm transition-colors"
                        @click="download"
                    >
                        <Download class="w-3.5 h-3.5" />
                        {{ __('Download') }}
                    </button>
                </div>

                <!-- PDF iframe -->
                <div
                    class="overflow-auto bg-paper-100"
                    :style="{ height: height }"
                >
                    <div
                        class="origin-top-left transition-transform duration-150"
                        :style="{
                            transform: `scale(${zoom / 100})`,
                            width:     zoom !== 100 ? `${100 * 100 / zoom}%` : '100%',
                            height:    zoom !== 100 ? `${100 * 100 / zoom}%` : '100%',
                        }"
                    >
                        <iframe
                            :src="iframeSrc"
                            class="w-full h-full border-none"
                            :style="{ height: height }"
                            :title="__('PDF Viewer')"
                            loading="lazy"
                        />
                    </div>
                </div>
            </div>

        </template>
    </FieldWrapper>
</template>
