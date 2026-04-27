<script setup lang="ts">
import { usePage } from '@inertiajs/vue3'
import { Button }                       from '@/components/ui/button'
import { Badge }                        from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { CheckCircle }                  from 'lucide-vue-next'
import type { DarejerSharedProps }      from '@/types/darejer'

const page = usePage<DarejerSharedProps>()

const checks = [
    { label: 'Vue 3' },
    { label: 'Inertia v3' },
    { label: 'Tailwind CSS v4' },
    { label: 'shadcn-vue' },
    { label: 'Lucide Icons' },
    { label: 'TypeScript' },
]
</script>

<template>
    <div class="min-h-screen bg-background flex items-center justify-center p-8">
        <Card class="w-full max-w-lg shadow-xl">
            <CardHeader class="pb-4">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-8 h-8 rounded-lg bg-brand-500 flex items-center justify-center">
                        <span class="text-white font-bold text-sm">D</span>
                    </div>
                    <CardTitle class="text-2xl font-semibold tracking-tight">
                        Darejer Playground
                    </CardTitle>
                </div>
                <p class="text-muted-foreground text-sm">
                    Frontend stack is operational. All systems ready.
                </p>
            </CardHeader>

            <CardContent class="space-y-4">
                <div class="space-y-2">
                    <div
                        v-for="check in checks"
                        :key="check.label"
                        class="flex items-center justify-between py-2 px-3 rounded-md bg-muted/50"
                    >
                        <span class="text-sm font-medium">{{ check.label }}</span>
                        <div class="flex items-center gap-1.5 text-success-600">
                            <CheckCircle class="w-4 h-4" />
                            <span class="text-xs font-medium">Ready</span>
                        </div>
                    </div>
                </div>

                <div class="rounded-md border p-3 bg-card space-y-1">
                    <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-2">
                        Inertia Shared Props from DarejerServiceProvider
                    </p>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-muted-foreground">Languages:</span>
                        <Badge
                            v-for="lang in page.props.darejer.languages"
                            :key="lang"
                            variant="secondary"
                            class="text-xs"
                        >
                            {{ lang }}
                        </Badge>
                    </div>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-xs text-muted-foreground">Default:</span>
                        <Badge variant="outline" class="text-xs">
                            {{ page.props.darejer.default_language }}
                        </Badge>
                    </div>
                </div>

                <Button class="w-full bg-brand-500 hover:bg-brand-600 text-white">
                    Continue to App
                </Button>
            </CardContent>
        </Card>
    </div>
</template>
