<script setup lang="ts">
import { usePage, Link } from '@inertiajs/vue3'
import { ChevronRight } from 'lucide-vue-next'
import type { DarejerSharedProps } from '@/types/darejer'
import useTranslation from '@/composables/useTranslation'

const page = usePage<DarejerSharedProps>()

const { __ } = useTranslation()
</script>

<template>
  <nav
    v-if="page.props.breadcrumbs?.length"
    class="flex items-center gap-1 text-[12px] text-ink-500 tabular-nums"
    :aria-label="__('Breadcrumb')"
  >
    <template v-for="(crumb, i) in page.props.breadcrumbs" :key="i">
      <Link
        v-if="crumb.url && i < page.props.breadcrumbs.length - 1"
        :href="crumb.url"
        class="rounded-[2px] px-1 py-0.5 text-brand-700 transition-colors hover:text-brand-800 hover:underline"
      >
        {{ crumb.label }}
      </Link>
      <span v-else class="py-0.5 pe-1 font-semibold text-ink-900">
        {{ crumb.label }}
      </span>

      <ChevronRight
        v-if="i < page.props.breadcrumbs.length - 1"
        class="h-3 w-3 text-ink-400 rtl:rotate-180"
      />
    </template>
  </nav>
</template>
