<script setup lang="ts">
import { Download, ZoomIn, ZoomOut, RotateCw, Maximize2, FileText } from 'lucide-vue-next'
import { ref, computed } from 'vue'
import FieldWrapper from '@/components/darejer/FieldWrapper.vue'
import useTranslation from '@/composables/useTranslation'
import type { DarejerComponent } from '@/types/darejer'

const { __ } = useTranslation()

const props = defineProps<{
  component: DarejerComponent
  record: Record<string, unknown>
  errors: Record<string, string>
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

const height = computed(() => `${(props.component.height as number) ?? 600}px`)
const showToolbar = computed(() => props.component.showToolbar !== false)
const canDownload = computed(() => props.component.download !== false)

// Browser PDF rendering doesn't expose a JS zoom API, so we scale the iframe
// container with CSS transform and compensate its inner size so no content
// gets clipped at lower zoom levels.
const zoom = ref(100)
const minZoom = 50
const maxZoom = 200

function zoomIn() {
  zoom.value = Math.min(maxZoom, zoom.value + 10)
}
function zoomOut() {
  zoom.value = Math.max(minZoom, zoom.value - 10)
}
function resetZoom() {
  zoom.value = 100
}

const containerEl = ref<HTMLElement | null>(null)
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
  a.href = pdfSrc.value
  a.download = 'document.pdf'
  a.target = '_blank'
  a.click()
}

const iframeSrc = computed(() => {
  if (!pdfSrc.value) return ''
  const sep = pdfSrc.value.includes('?') ? '&' : '#'
  return `${pdfSrc.value}${sep}toolbar=${showToolbar.value ? 1 : 0}&navpanes=0`
})
</script>

<template>
  <FieldWrapper :component="component" :record="record" :errors="errors" class="col-span-full">
    <template #default>
      <!-- No PDF -->
      <div
        v-if="!pdfSrc"
        class="border-paper-300 bg-paper-50 text-ink-400 flex flex-col items-center justify-center gap-2 rounded-md border border-dashed"
        :style="{ height: height }"
      >
        <FileText class="h-8 w-8" />
        <span class="text-sm">{{ __('No PDF file specified.') }}</span>
      </div>

      <!-- PDF viewer -->
      <div v-else ref="containerEl" class="border-paper-200 bg-card flex flex-col overflow-hidden rounded-md border">
        <!-- Toolbar -->
        <div class="border-paper-200 bg-paper-75 flex items-center gap-1 border-b px-3 py-1.5">
          <button
            type="button"
            class="text-ink-500 hover:bg-paper-200 flex h-7 w-7 items-center justify-center rounded-sm transition-colors disabled:opacity-40"
            :disabled="zoom <= minZoom"
            :title="__('Zoom out')"
            @click="zoomOut"
          >
            <ZoomOut class="h-3.5 w-3.5" />
          </button>

          <span class="text-ink-500 w-10 text-center font-mono text-xs tabular-nums select-none"> {{ zoom }}% </span>

          <button
            type="button"
            class="text-ink-500 hover:bg-paper-200 flex h-7 w-7 items-center justify-center rounded-sm transition-colors disabled:opacity-40"
            :disabled="zoom >= maxZoom"
            :title="__('Zoom in')"
            @click="zoomIn"
          >
            <ZoomIn class="h-3.5 w-3.5" />
          </button>

          <button
            type="button"
            class="text-ink-500 hover:bg-paper-200 flex h-7 w-7 items-center justify-center rounded-sm text-xs transition-colors"
            :title="__('Reset zoom')"
            @click="resetZoom"
          >
            <RotateCw class="h-3 w-3" />
          </button>

          <div class="bg-paper-300 mx-1 h-4 w-px" />

          <button
            type="button"
            class="text-ink-500 hover:bg-paper-200 flex h-7 w-7 items-center justify-center rounded-sm transition-colors"
            :title="__('Fullscreen')"
            @click="toggleFullscreen"
          >
            <Maximize2 class="h-3.5 w-3.5" />
          </button>

          <button
            v-if="canDownload"
            type="button"
            class="text-brand-600 hover:bg-brand-50 ms-auto flex h-7 items-center gap-1.5 rounded-sm px-2.5 text-xs font-medium transition-colors"
            @click="download"
          >
            <Download class="h-3.5 w-3.5" />
            {{ __('Download') }}
          </button>
        </div>

        <!-- PDF iframe -->
        <div class="bg-paper-100 overflow-auto" :style="{ height: height }">
          <div
            class="origin-top-left transition-transform duration-150"
            :style="{
              transform: `scale(${zoom / 100})`,
              width: zoom !== 100 ? `${(100 * 100) / zoom}%` : '100%',
              height: zoom !== 100 ? `${(100 * 100) / zoom}%` : '100%',
            }"
          >
            <iframe
              :src="iframeSrc"
              class="h-full w-full border-none"
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
