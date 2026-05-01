<script setup lang="ts">
import { ref, computed }    from 'vue'
import { UploadCloud, X, FileText, ImageIcon, AlertCircle } from 'lucide-vue-next'
import FieldWrapper         from '@/components/darejer/FieldWrapper.vue'
import useTranslation       from '@/composables/useTranslation'
import type { DarejerComponent } from '@/types/darejer'

const { __ } = useTranslation()

const props = defineProps<{
    component: DarejerComponent
    record:    Record<string, unknown>
    errors:    Record<string, string>
    formData?: Record<string, unknown>
}>()

const emit = defineEmits<{ (e: 'update', name: string, value: unknown): void }>()

interface FileEntry {
    id:      number
    file:    File
    name:    string
    size:    number
    type:    string
    preview: string | null
    error:   string | null
}

let nextId    = 0
const files    = ref<FileEntry[]>([])
const dragging = ref(false)
const inputEl  = ref<HTMLInputElement | null>(null)

const isMultiple  = computed(() => !!props.component.multiple)
const accept      = computed(() => (props.component.accept as string[] | undefined)?.join(',') ?? undefined)
const maxSize     = computed(() => props.component.maxSize as number | undefined)
const maxFiles    = computed(() => props.component.maxFiles as number | undefined)
const showPreview = computed(() => props.component.preview !== false)
const isImage     = computed(() => !!props.component.image)

function formatSize(bytes: number): string {
    if (bytes < 1024)        return `${bytes} B`
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}

function validateFile(file: File): string | null {
    if (maxSize.value && file.size > maxSize.value * 1024) {
        return __('File exceeds :max KB limit.', { max: maxSize.value })
    }
    const acceptTypes = props.component.accept as string[] | undefined
    if (acceptTypes?.length) {
        const ok = acceptTypes.some(t => {
            if (t.endsWith('/*')) return file.type.startsWith(t.replace('/*', '/'))
            if (t.startsWith('.')) return file.name.endsWith(t)
            return file.type === t
        })
        if (!ok) return __('File type not allowed.')
    }
    return null
}

function createEntry(file: File): FileEntry {
    const error   = validateFile(file)
    const preview = (showPreview.value && file.type.startsWith('image/') && !error)
        ? URL.createObjectURL(file)
        : null

    return { id: nextId++, file, name: file.name, size: file.size, type: file.type, preview, error }
}

function addFiles(fileList: FileList | null) {
    if (!fileList) return

    const incoming = Array.from(fileList)
    if (incoming.length === 0) return

    if (!isMultiple.value) {
        files.value.forEach(f => f.preview && URL.revokeObjectURL(f.preview))
        files.value = [createEntry(incoming[0])]
    } else {
        const remaining = maxFiles.value
            ? maxFiles.value - files.value.length
            : incoming.length

        const toAdd = incoming.slice(0, remaining).map(createEntry)
        files.value = [...files.value, ...toAdd]
    }

    emitFiles()
}

function removeFile(id: number) {
    const entry = files.value.find(f => f.id === id)
    if (entry?.preview) URL.revokeObjectURL(entry.preview)
    files.value = files.value.filter(f => f.id !== id)
    emitFiles()
}

function emitFiles() {
    const validFiles = files.value.filter(f => !f.error).map(f => f.file)
    emit('update', props.component.name, isMultiple.value ? validFiles : (validFiles[0] ?? null))
}

function onDragOver(e: DragEvent) {
    e.preventDefault()
    dragging.value = true
}

function onDragLeave() {
    dragging.value = false
}

function onDrop(e: DragEvent) {
    e.preventDefault()
    dragging.value = false
    addFiles(e.dataTransfer?.files ?? null)
}

function onFileInput(e: Event) {
    addFiles((e.target as HTMLInputElement).files)
    if (inputEl.value) inputEl.value.value = ''
}

function openPicker() {
    if (props.component.disabled) return
    inputEl.value?.click()
}

const atMax = computed(() =>
    maxFiles.value ? files.value.length >= maxFiles.value : false
)

const dropLabel = computed(() => {
    if (isImage.value) return __('Drop images here or click to browse')
    return __('Drop files here or click to browse')
})
</script>

<template>
    <FieldWrapper :component="component" :record="record" :errors="errors" :form-data="formData">
        <template #default="{ hasError }">

            <!-- Hidden file input -->
            <input
                ref="inputEl"
                type="file"
                :accept="accept"
                :multiple="isMultiple"
                class="hidden"
                @change="onFileInput"
            />

            <!-- Drop zone -->
            <div
                v-if="!atMax || files.length === 0"
                class="flex flex-col items-center justify-center gap-2 p-6 border-2 border-dashed
                       rounded-lg cursor-pointer transition-colors duration-150 text-center"
                :class="[
                    dragging
                        ? 'border-brand-400 bg-brand-50'
                        : hasError
                            ? 'border-danger-300 bg-danger-50/30'
                            : 'border-slate-200 bg-slate-50 hover:border-slate-300 hover:bg-slate-75',
                    (component.disabled as boolean) ? 'opacity-50 cursor-not-allowed' : '',
                ]"
                @dragover="onDragOver"
                @dragleave="onDragLeave"
                @drop="onDrop"
                @click="openPicker"
            >
                <UploadCloud
                    class="w-8 h-8"
                    :class="dragging ? 'text-brand-500' : 'text-slate-300'"
                />
                <div>
                    <p class="text-sm font-medium text-slate-600">{{ dropLabel }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">
                        <span v-if="accept">{{ accept }}</span>
                        <span v-if="maxSize"> · {{ __('max :size KB', { size: maxSize }) }}</span>
                        <span v-if="maxFiles && isMultiple"> · {{ __('up to :max files', { max: maxFiles }) }}</span>
                    </p>
                </div>
            </div>

            <!-- File list -->
            <div v-if="files.length > 0" class="mt-2 space-y-1.5">
                <div
                    v-for="entry in files"
                    :key="entry.id"
                    class="flex items-center gap-2 p-2 border rounded-md"
                    :class="entry.error ? 'border-danger-200 bg-danger-50' : 'border-slate-200 bg-card'"
                >
                    <!-- Preview or icon -->
                    <div class="shrink-0">
                        <img
                            v-if="entry.preview"
                            :src="entry.preview"
                            :alt="entry.name"
                            class="w-10 h-10 object-cover rounded border border-slate-200"
                        />
                        <div
                            v-else
                            class="w-10 h-10 flex items-center justify-center rounded
                                   bg-slate-100 border border-slate-200"
                        >
                            <ImageIcon v-if="entry.type.startsWith('image/')" class="w-5 h-5 text-slate-400" />
                            <FileText v-else class="w-5 h-5 text-slate-400" />
                        </div>
                    </div>

                    <!-- File info -->
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-slate-700 truncate">{{ entry.name }}</p>
                        <p v-if="entry.error" class="text-xs text-danger-600 flex items-center gap-1">
                            <AlertCircle class="w-3 h-3" />
                            {{ entry.error }}
                        </p>
                        <p v-else class="text-xs text-slate-400">{{ formatSize(entry.size) }}</p>
                    </div>

                    <!-- Remove -->
                    <button
                        type="button"
                        class="shrink-0 text-slate-300 hover:text-danger-600 transition-colors"
                        @click="removeFile(entry.id)"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>
            </div>

        </template>
    </FieldWrapper>
</template>
