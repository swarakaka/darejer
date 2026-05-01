<script setup lang="ts">
import { useEditor, EditorContent }     from '@tiptap/vue-3'
import StarterKit                        from '@tiptap/starter-kit'
import Underline                         from '@tiptap/extension-underline'
import TextAlign                         from '@tiptap/extension-text-align'
import Link                              from '@tiptap/extension-link'
import Placeholder                       from '@tiptap/extension-placeholder'
import CharacterCount                    from '@tiptap/extension-character-count'
import { ref, computed, onBeforeUnmount } from 'vue'
import {
    Bold, Italic, Underline as UnderlineIcon,
    Strikethrough, List, ListOrdered,
    Quote, Code, Link as LinkIcon,
    AlignLeft, AlignCenter, AlignRight,
    Heading1, Heading2, Heading3,
    Undo, Redo,
} from 'lucide-vue-next'
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

const rawValue = String(
    (props.formData ?? props.record)[props.component.name]
    ?? props.component.default
    ?? ''
)

const maxChars   = computed(() => props.component.maxCharacters as number | undefined)
const minHeight  = computed(() => `${(props.component.minHeight as number) ?? 200}px`)
const maxHeightV = computed(() => {
    const v = props.component.maxHeight as number | undefined
    return v ? `${v}px` : 'none'
})

const editor = useEditor({
    content: rawValue,
    editable: !props.component.disabled && !props.component.readonly,
    extensions: [
        StarterKit,
        Underline,
        TextAlign.configure({ types: ['heading', 'paragraph'] }),
        Link.configure({ openOnClick: false }),
        Placeholder.configure({
            placeholder: (props.component.placeholder as string) ?? __('Enter text…'),
        }),
        ...(maxChars.value ? [CharacterCount.configure({ limit: maxChars.value })] : []),
    ],
    onUpdate: ({ editor }) => {
        emit('update', props.component.name, editor.getHTML())
    },
})

onBeforeUnmount(() => editor.value?.destroy())

const allowedTools = computed(() =>
    props.component.toolbar as string[] | null | undefined
)

function toolAllowed(name: string): boolean {
    if (!allowedTools.value?.length) return true
    return allowedTools.value.includes(name)
}

const linkDialogOpen = ref(false)
const linkUrl        = ref('')

function openLinkDialog() {
    const prev = editor.value?.getAttributes('link').href ?? ''
    linkUrl.value = prev
    linkDialogOpen.value = true
}

function applyLink() {
    if (!linkUrl.value) {
        editor.value?.chain().focus().unsetLink().run()
    } else {
        editor.value?.chain().focus().setLink({ href: linkUrl.value }).run()
    }
    linkDialogOpen.value = false
}

const charCount   = computed(() => editor.value?.storage.characterCount?.characters() ?? 0)
const isOverLimit = computed(() => maxChars.value ? charCount.value > maxChars.value : false)
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
                class="darejer-rte"
                :class="[
                    hasError ? '!border-danger-600' : '',
                    (component.disabled || component.readonly) ? 'is-disabled' : '',
                ]"
            >
                <!-- Toolbar -->
                <div
                    v-if="!component.readonly"
                    class="darejer-rte-toolbar"
                >
                    <!-- History -->
                    <template v-if="toolAllowed('undo')">
                        <button type="button" class="darejer-rte-btn" :title="__('Undo')"
                            @click="editor?.chain().focus().undo().run()">
                            <Undo class="w-4 h-4" />
                        </button>
                        <button type="button" class="darejer-rte-btn" :title="__('Redo')"
                            @click="editor?.chain().focus().redo().run()">
                            <Redo class="w-4 h-4" />
                        </button>
                        <div class="darejer-rte-divider" />
                    </template>

                    <!-- Headings -->
                    <template v-if="toolAllowed('heading')">
                        <button type="button" class="darejer-rte-btn" :title="__('Heading 1')"
                            :class="{ 'is-active': editor?.isActive('heading', { level: 1 }) }"
                            @click="editor?.chain().focus().toggleHeading({ level: 1 }).run()">
                            <Heading1 class="w-4 h-4" />
                        </button>
                        <button type="button" class="darejer-rte-btn" :title="__('Heading 2')"
                            :class="{ 'is-active': editor?.isActive('heading', { level: 2 }) }"
                            @click="editor?.chain().focus().toggleHeading({ level: 2 }).run()">
                            <Heading2 class="w-4 h-4" />
                        </button>
                        <button type="button" class="darejer-rte-btn" :title="__('Heading 3')"
                            :class="{ 'is-active': editor?.isActive('heading', { level: 3 }) }"
                            @click="editor?.chain().focus().toggleHeading({ level: 3 }).run()">
                            <Heading3 class="w-4 h-4" />
                        </button>
                        <div class="darejer-rte-divider" />
                    </template>

                    <!-- Inline formatting -->
                    <button type="button" class="darejer-rte-btn" :title="__('Bold')"
                        :class="{ 'is-active': editor?.isActive('bold') }"
                        @click="editor?.chain().focus().toggleBold().run()">
                        <Bold class="w-4 h-4" />
                    </button>
                    <button type="button" class="darejer-rte-btn" :title="__('Italic')"
                        :class="{ 'is-active': editor?.isActive('italic') }"
                        @click="editor?.chain().focus().toggleItalic().run()">
                        <Italic class="w-4 h-4" />
                    </button>
                    <template v-if="toolAllowed('underline')">
                        <button type="button" class="darejer-rte-btn" :title="__('Underline')"
                            :class="{ 'is-active': editor?.isActive('underline') }"
                            @click="editor?.chain().focus().toggleUnderline().run()">
                            <UnderlineIcon class="w-4 h-4" />
                        </button>
                    </template>
                    <template v-if="toolAllowed('strike')">
                        <button type="button" class="darejer-rte-btn" :title="__('Strikethrough')"
                            :class="{ 'is-active': editor?.isActive('strike') }"
                            @click="editor?.chain().focus().toggleStrike().run()">
                            <Strikethrough class="w-4 h-4" />
                        </button>
                    </template>
                    <div class="darejer-rte-divider" />

                    <!-- Lists -->
                    <template v-if="toolAllowed('bulletList')">
                        <button type="button" class="darejer-rte-btn" :title="__('Bullet list')"
                            :class="{ 'is-active': editor?.isActive('bulletList') }"
                            @click="editor?.chain().focus().toggleBulletList().run()">
                            <List class="w-4 h-4" />
                        </button>
                    </template>
                    <template v-if="toolAllowed('orderedList')">
                        <button type="button" class="darejer-rte-btn" :title="__('Numbered list')"
                            :class="{ 'is-active': editor?.isActive('orderedList') }"
                            @click="editor?.chain().focus().toggleOrderedList().run()">
                            <ListOrdered class="w-4 h-4" />
                        </button>
                    </template>
                    <template v-if="toolAllowed('blockquote')">
                        <button type="button" class="darejer-rte-btn" :title="__('Blockquote')"
                            :class="{ 'is-active': editor?.isActive('blockquote') }"
                            @click="editor?.chain().focus().toggleBlockquote().run()">
                            <Quote class="w-4 h-4" />
                        </button>
                    </template>
                    <template v-if="toolAllowed('code')">
                        <button type="button" class="darejer-rte-btn" :title="__('Code')"
                            :class="{ 'is-active': editor?.isActive('code') }"
                            @click="editor?.chain().focus().toggleCode().run()">
                            <Code class="w-4 h-4" />
                        </button>
                    </template>
                    <div class="darejer-rte-divider" />

                    <!-- Alignment -->
                    <template v-if="toolAllowed('align')">
                        <button type="button" class="darejer-rte-btn" :title="__('Align left')"
                            :class="{ 'is-active': editor?.isActive({ textAlign: 'left' }) }"
                            @click="editor?.chain().focus().setTextAlign('left').run()">
                            <AlignLeft class="w-4 h-4" />
                        </button>
                        <button type="button" class="darejer-rte-btn" :title="__('Align center')"
                            :class="{ 'is-active': editor?.isActive({ textAlign: 'center' }) }"
                            @click="editor?.chain().focus().setTextAlign('center').run()">
                            <AlignCenter class="w-4 h-4" />
                        </button>
                        <button type="button" class="darejer-rte-btn" :title="__('Align right')"
                            :class="{ 'is-active': editor?.isActive({ textAlign: 'right' }) }"
                            @click="editor?.chain().focus().setTextAlign('right').run()">
                            <AlignRight class="w-4 h-4" />
                        </button>
                        <div class="darejer-rte-divider" />
                    </template>

                    <!-- Link -->
                    <template v-if="toolAllowed('link')">
                        <button type="button" class="darejer-rte-btn" :title="__('Link')"
                            :class="{ 'is-active': editor?.isActive('link') }"
                            @click="openLinkDialog">
                            <LinkIcon class="w-4 h-4" />
                        </button>
                    </template>
                </div>

                <!-- Editor content -->
                <div
                    class="darejer-rte-content"
                    :style="{
                        minHeight: minHeight,
                        maxHeight: maxHeightV,
                        overflowY: maxHeightV !== 'none' ? 'auto' : 'visible',
                    }"
                >
                    <EditorContent :editor="editor" />
                </div>

                <!-- Character count footer -->
                <div
                    v-if="maxChars"
                    class="darejer-rte-footer"
                    :class="{ 'is-over-limit': isOverLimit }"
                >
                    {{ charCount }} / {{ maxChars }}
                </div>
            </div>

            <!-- Link dialog -->
            <div
                v-if="linkDialogOpen"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/20"
                @click.self="linkDialogOpen = false"
            >
                <div class="bg-card border border-paper-200 rounded-md p-4 w-80 flex flex-col gap-3">
                    <p class="text-sm font-semibold text-ink-800">{{ __('Insert link') }}</p>
                    <input
                        v-model="linkUrl"
                        type="url"
                        placeholder="https://example.com"
                        class="w-full h-8 px-2.5 text-sm border border-paper-300 rounded-sm"
                        @keydown.enter="applyLink"
                    />
                    <div class="flex justify-end gap-2">
                        <button
                            type="button"
                            class="h-8 px-3 text-sm border border-paper-300 rounded-sm hover:bg-paper-100"
                            @click="linkDialogOpen = false"
                        >
                            {{ __('Cancel') }}
                        </button>
                        <button
                            type="button"
                            class="h-8 px-3 text-sm bg-brand-600 text-white rounded-sm hover:bg-brand-700"
                            @click="applyLink"
                        >
                            {{ __('Apply') }}
                        </button>
                    </div>
                </div>
            </div>

        </template>
    </FieldWrapper>
</template>
