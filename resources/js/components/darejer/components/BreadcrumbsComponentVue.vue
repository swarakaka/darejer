<script setup lang="ts">
import { ChevronRight } from 'lucide-vue-next'
import type { DarejerComponent } from '@/types/darejer'

const props = defineProps<{
  component: DarejerComponent
  record: Record<string, unknown>
  errors: Record<string, string>
  formData?: Record<string, unknown>
}>()

interface Crumb {
  label: string
  url?: string
}

const crumbs = (props.component.crumbs as Crumb[]) ?? []
</script>

<template>
  <nav
    v-if="crumbs.length"
    class="text-ink-400 col-span-full flex items-center gap-1 py-1 text-xs tabular-nums"
    aria-label="Breadcrumb"
  >
    <template v-for="(crumb, index) in crumbs" :key="index">
      <a v-if="crumb.url && index < crumbs.length - 1" :href="crumb.url" class="hover:text-brand-600 transition-colors">
        {{ crumb.label }}
      </a>
      <span v-else class="text-ink-700 font-medium">
        {{ crumb.label }}
      </span>
      <ChevronRight v-if="index < crumbs.length - 1" class="text-ink-300 h-3 w-3" />
    </template>
  </nav>
</template>
