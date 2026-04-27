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
                class="hover:text-brand-600 transition-colors"
            >
                {{ crumb.label }}
            </Link>
            <span
                v-else
                class="text-ink-500 font-medium"
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
