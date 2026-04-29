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
        class="flex items-center gap-1 text-xs text-ink-400 tabular-nums"
        :aria-label="__('Breadcrumb')"
    >
        <template
            v-for="(crumb, i) in page.props.breadcrumbs"
            :key="i"
        >
            <Link
                v-if="crumb.url && i < page.props.breadcrumbs.length - 1"
                :href="crumb.url"
                class="px-1 py-0.5 rounded-sm hover:text-brand-700 hover:bg-paper-100 transition-colors"
            >
                {{ crumb.label }}
            </Link>
            <span
                v-else
                class="px-1 py-0.5 text-ink-700 font-semibold"
            >
                {{ crumb.label }}
            </span>

            <ChevronRight
                v-if="i < page.props.breadcrumbs.length - 1"
                class="w-3 h-3 text-ink-300 rtl:rotate-180"
            />
        </template>
    </nav>
</template>
