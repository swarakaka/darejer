<script setup lang="ts">
import { computed } from 'vue'
import {
    Circle, Star, Heart, Check, CheckCircle, X, Plus, Minus,
    AlertCircle, AlertTriangle, Info, HelpCircle, Bell,
    User, Users, Settings, Shield, Lock,
    Home, LayoutDashboard, Package, Package2, FileText,
    Folder, FolderOpen, Archive, Inbox,
    Search, Filter, SlidersHorizontal,
    ChevronDown, ChevronRight, ChevronLeft, ChevronUp,
    ArrowRight, ArrowLeft, ArrowUp, ArrowDown,
    Upload, Download, Trash2, Pencil, Eye,
    Calendar, Clock, Tag, Layers, Globe,
    BarChart2, BarChart3, TrendingUp, Database, Cpu,
    Mail, Phone, MapPin, Building, CreditCard,
    ShoppingCart, Truck, Boxes,
    Wrench, BookOpen,
} from 'lucide-vue-next'
import type { DarejerComponent } from '@/types/darejer'

const props = defineProps<{
    component: DarejerComponent
    record:    Record<string, unknown>
    errors:    Record<string, string>
    formData?: Record<string, unknown>
}>()

const iconMap: Record<string, unknown> = {
    Circle, Star, Heart, Check, CheckCircle, X, Plus, Minus,
    AlertCircle, AlertTriangle, Info, HelpCircle, Bell,
    User, Users, Settings, Shield, Lock,
    Home, LayoutDashboard, Package, Package2, FileText,
    Folder, FolderOpen, Archive, Inbox,
    Search, Filter, SlidersHorizontal,
    ChevronDown, ChevronRight, ChevronLeft, ChevronUp,
    ArrowRight, ArrowLeft, ArrowUp, ArrowDown,
    Upload, Download, Trash2, Pencil, Eye,
    Calendar, Clock, Tag, Layers, Globe,
    BarChart2, BarChart3, TrendingUp, Database, Cpu,
    Mail, Phone, MapPin, Building, CreditCard,
    ShoppingCart, Truck, Boxes,
    Wrench, BookOpen,
}

const sizeMap: Record<string, string> = {
    xs: 'w-3 h-3',
    sm: 'w-3.5 h-3.5',
    md: 'w-4 h-4',
    lg: 'w-5 h-5',
    xl: 'w-6 h-6',
}

const resolvedIcon = computed(() => {
    const name = props.component.iconName as string | undefined
    return name ? (iconMap[name] ?? Circle) : Circle
})

const sizeClass = computed(() => {
    const size = props.component.size as string | undefined
    return sizeMap[size ?? 'md'] ?? sizeMap.md
})

const colorClass = computed(() =>
    (props.component.color as string | undefined) ?? 'text-ink-500'
)

const title = computed(() =>
    (props.component.title as string | undefined) ?? undefined
)
</script>

<template>
    <div class="inline-flex items-center">
        <component
            :is="resolvedIcon"
            :class="[sizeClass, colorClass]"
            :title="title"
        />
    </div>
</template>
