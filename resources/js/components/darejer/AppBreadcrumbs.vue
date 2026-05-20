<script setup lang="ts">
import { usePage, Link } from '@inertiajs/vue3'
import { ChevronRight } from 'lucide-vue-next'
import useTranslation from '@/composables/useTranslation'
import type { DarejerSharedProps } from '@/types/darejer'

const page = usePage<DarejerSharedProps>()

const { __ } = useTranslation()
</script>

<template>
  <nav
    v-if="page.props.breadcrumbs?.length"
    class="text-ink-500 flex items-center gap-1 text-[12px] tabular-nums"
    :aria-label="__('Breadcrumb')"
  >
    <template v-for="(crumb, i) in page.props.breadcrumbs" :key="i">
      <Link
        v-if="crumb.url && i < page.props.breadcrumbs.length - 1"
        :href="crumb.url"
        class="text-brand-700 hover:text-brand-800 rounded-[2px] px-1 py-0.5 transition-colors hover:underline"
      >
        {{ crumb.label }}
      </Link>
      <span v-else class="text-ink-900 py-0.5 pe-1 font-semibold">
        {{ crumb.label }}
      </span>

      <ChevronRight v-if="i < page.props.breadcrumbs.length - 1" class="text-ink-400 h-3 w-3 rtl:rotate-180" />
    </template>
  </nav>
</template>
