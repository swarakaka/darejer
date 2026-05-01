<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import {
    VueFlow,
    useVueFlow,
    Position,
    type Node,
    type Edge,
} from '@vue-flow/core'
import { Background }  from '@vue-flow/background'
import { Controls }    from '@vue-flow/controls'
import { MiniMap }     from '@vue-flow/minimap'
import { useHttp }     from '@inertiajs/vue3'
import { Loader2 }     from 'lucide-vue-next'
import FieldWrapper    from '@/components/darejer/FieldWrapper.vue'
import type { DarejerComponent } from '@/types/darejer'

const props = defineProps<{
    component: DarejerComponent
    record:    Record<string, unknown>
    errors:    Record<string, string>
    formData?: Record<string, unknown>
}>()

const emit = defineEmits<{ (e: 'update', name: string, value: unknown): void }>()

const isReadonly   = computed(() => !!props.component.readonly)
const height       = computed(() => `${(props.component.height as number) ?? 400}px`)
const showMinimap  = computed(() => props.component.minimap    !== false)
const showControls = computed(() => props.component.controls   !== false)
const showBg       = computed(() => props.component.background !== false)

const nodes = ref<Node[]>([])
const edges = ref<Edge[]>([])

const http = useHttp<Record<string, never>, {
    nodes?: Record<string, unknown>[]
    edges?: Record<string, unknown>[]
}>()

function normalizeNode(n: Record<string, unknown>): Node {
    return {
        id:       String(n.id ?? ''),
        type:     (n.type as string) ?? 'default',
        label:    (n.label as string) ?? '',
        position: (n.position as { x: number; y: number }) ?? { x: 0, y: 0 },
        data:     { label: (n.label as string) ?? '' },
        sourcePosition: Position.Right,
        targetPosition: Position.Left,
    }
}

function normalizeEdge(e: Record<string, unknown>): Edge {
    return {
        id:       String(e.id ?? `${e.source}-${e.target}`),
        source:   String(e.source ?? ''),
        target:   String(e.target ?? ''),
        label:    (e.label as string) ?? undefined,
        type:     (e.type  as string) ?? 'default',
        animated: !!(e.animated),
    }
}

function loadStatic() {
    const staticNodes = (props.component.nodes as Record<string, unknown>[]) ?? []
    const staticEdges = (props.component.edges as Record<string, unknown>[]) ?? []
    nodes.value = staticNodes.map(normalizeNode)
    edges.value = staticEdges.map(normalizeEdge)
}

function loadFromUrl() {
    const dataUrl = props.component.dataUrl as string | undefined
    if (!dataUrl) return

    http.get(dataUrl, {
        onSuccess: (data) => {
            nodes.value = (data?.nodes ?? []).map(normalizeNode)
            edges.value = (data?.edges ?? []).map(normalizeEdge)
        },
    })
}

onMounted(() => {
    if (props.component.dataUrl) {
        loadFromUrl()
    } else {
        loadStatic()
    }
})

const { onNodesChange, onEdgesChange, applyNodeChanges, applyEdgeChanges } = useVueFlow()

onNodesChange((changes) => {
    if (isReadonly.value) return
    applyNodeChanges(changes)
    emitDiagramState()
})

onEdgesChange((changes) => {
    if (isReadonly.value) return
    applyEdgeChanges(changes)
    emitDiagramState()
})

function emitDiagramState() {
    const rawNodes: any[] = nodes.value as any
    const rawEdges: any[] = edges.value as any

    const nodeOut = rawNodes.map((n) => ({
        id:       n.id,
        type:     n.type,
        label:    n.data?.label ?? n.label,
        position: n.position,
    }))

    const edgeOut = rawEdges.map((e) => ({
        id:     e.id,
        source: e.source,
        target: e.target,
        label:  e.label,
    }))

    emit('update', props.component.name, { nodes: nodeOut, edges: edgeOut })
}
</script>

<template>
    <FieldWrapper
        :component="component"
        :record="record"
        :errors="errors"
        class="col-span-full"
    >
        <template #default>

            <!-- Loading -->
            <div
                v-if="http.processing"
                class="flex items-center justify-center border border-paper-200 rounded-md bg-paper-50"
                :style="{ height: height }"
            >
                <Loader2 class="w-5 h-5 animate-spin text-ink-400" />
            </div>

            <!-- Diagram -->
            <div
                v-else
                class="border border-paper-200 rounded-md overflow-hidden bg-card"
                :style="{ height: height }"
            >
                <VueFlow
                    :nodes="nodes"
                    :edges="edges"
                    :nodes-draggable="!isReadonly"
                    :nodes-connectable="!isReadonly"
                    :elements-selectable="!isReadonly"
                    :zoom-on-scroll="true"
                    :pan-on-drag="true"
                    fit-view-on-init
                    class="w-full h-full"
                >
                    <Background v-if="showBg" :gap="16" :size="1" />
                    <Controls  v-if="showControls" :show-interactive="!isReadonly" />
                    <MiniMap   v-if="showMinimap" />
                </VueFlow>
            </div>

        </template>
    </FieldWrapper>
</template>
