<script setup lang="ts">
import { ref, computed }    from 'vue'
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
}                           from '@/components/ui/popover'
import { Clock, ChevronUp, ChevronDown } from 'lucide-vue-next'
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

const open = ref(false)

const rawValue = (props.formData ?? props.record)[props.component.name]
    ?? props.component.default ?? null

function parseTime(v: unknown): { h: number; m: number; s: number } {
    if (typeof v === 'string' && v.match(/^\d{2}:\d{2}/)) {
        const [h, m, s] = v.split(':').map(Number)
        return { h: h ?? 0, m: m ?? 0, s: s ?? 0 }
    }
    return { h: 0, m: 0, s: 0 }
}

const time = ref(parseTime(rawValue))

const withSeconds = computed(() => !!props.component.seconds)
const is12h       = computed(() => !!props.component.hour12)
const step        = computed(() => (props.component.step as number) ?? 1)

const meridiem = ref<'AM' | 'PM'>(time.value.h < 12 ? 'AM' : 'PM')

function displayHour(h: number): number {
    if (!is12h.value) return h
    const h12 = h % 12
    return h12 === 0 ? 12 : h12
}

const displayValue = computed(() => {
    const { h, m, s } = time.value
    const hh = is12h.value ? String(displayHour(h)).padStart(2, '0') : String(h).padStart(2, '0')
    const mm = String(m).padStart(2, '0')
    const ss = String(s).padStart(2, '0')
    const suffix = is12h.value ? ` ${meridiem.value}` : ''
    return withSeconds.value ? `${hh}:${mm}:${ss}${suffix}` : `${hh}:${mm}${suffix}`
})

function emitValue() {
    const { h, m, s } = time.value
    const val = withSeconds.value
        ? `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`
        : `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}`
    emit('update', props.component.name, val)
}

function increment(unit: 'h' | 'm' | 's') {
    if (unit === 'h')      time.value.h = (time.value.h + 1) % 24
    else if (unit === 'm') time.value.m = (time.value.m + step.value) % 60
    else                   time.value.s = (time.value.s + 1) % 60
    emitValue()
}

function decrement(unit: 'h' | 'm' | 's') {
    if (unit === 'h')      time.value.h = (time.value.h - 1 + 24) % 24
    else if (unit === 'm') time.value.m = (time.value.m - step.value + 60) % 60
    else                   time.value.s = (time.value.s - 1 + 60) % 60
    emitValue()
}

function toggleMeridiem() {
    if (meridiem.value === 'AM') {
        meridiem.value = 'PM'
        time.value.h = (time.value.h + 12) % 24
    } else {
        meridiem.value = 'AM'
        time.value.h = time.value.h % 12
    }
    emitValue()
}

function onUnitInput(unit: 'h' | 'm' | 's', e: Event) {
    const val = parseInt((e.target as HTMLInputElement).value) || 0
    if (unit === 'h') time.value.h = Math.min(23, Math.max(0, val))
    if (unit === 'm') time.value.m = Math.min(59, Math.max(0, val))
    if (unit === 's') time.value.s = Math.min(59, Math.max(0, val))
    emitValue()
}
</script>

<template>
    <FieldWrapper :component="component" :record="record" :errors="errors" :form-data="formData">
        <template #default="{ hasError }">
            <Popover :open="open" @update:open="open = $event">
                <PopoverTrigger as-child>
                    <button
                        type="button"
                        :disabled="(component.disabled as boolean)"
                        class="flex items-center justify-between w-full h-[2.125rem] px-2.5
                               text-sm bg-white border rounded transition-colors duration-100
                               disabled:opacity-50 disabled:cursor-not-allowed"
                        :class="[
                            hasError ? 'border-danger-600' : 'border-slate-300',
                            open ? 'border-brand-500 ring-1 ring-brand-500/20' : 'hover:border-slate-400',
                        ]"
                    >
                        <span class="text-slate-900 font-mono">{{ displayValue }}</span>
                        <Clock class="w-3.5 h-3.5 text-slate-400" />
                    </button>
                </PopoverTrigger>

                <PopoverContent class="p-3 w-auto" align="start">
                    <div class="flex items-center gap-2">

                        <!-- Hour -->
                        <div class="flex flex-col items-center gap-1">
                            <button type="button" class="p-0.5 text-slate-400 hover:text-slate-700" @click="increment('h')">
                                <ChevronUp class="w-4 h-4" />
                            </button>
                            <input
                                type="number"
                                :value="is12h ? displayHour(time.h) : time.h"
                                min="0"
                                :max="is12h ? 12 : 23"
                                class="w-10 h-8 text-center text-sm font-mono border border-slate-200 rounded"
                                @change="onUnitInput('h', $event)"
                            />
                            <button type="button" class="p-0.5 text-slate-400 hover:text-slate-700" @click="decrement('h')">
                                <ChevronDown class="w-4 h-4" />
                            </button>
                            <span class="text-xs text-slate-400">{{ __('hr') }}</span>
                        </div>

                        <span class="text-slate-400 font-mono text-lg mb-4">:</span>

                        <!-- Minute -->
                        <div class="flex flex-col items-center gap-1">
                            <button type="button" class="p-0.5 text-slate-400 hover:text-slate-700" @click="increment('m')">
                                <ChevronUp class="w-4 h-4" />
                            </button>
                            <input
                                type="number"
                                :value="time.m"
                                min="0"
                                max="59"
                                class="w-10 h-8 text-center text-sm font-mono border border-slate-200 rounded"
                                @change="onUnitInput('m', $event)"
                            />
                            <button type="button" class="p-0.5 text-slate-400 hover:text-slate-700" @click="decrement('m')">
                                <ChevronDown class="w-4 h-4" />
                            </button>
                            <span class="text-xs text-slate-400">{{ __('min') }}</span>
                        </div>

                        <!-- Seconds -->
                        <template v-if="withSeconds">
                            <span class="text-slate-400 font-mono text-lg mb-4">:</span>
                            <div class="flex flex-col items-center gap-1">
                                <button type="button" class="p-0.5 text-slate-400 hover:text-slate-700" @click="increment('s')">
                                    <ChevronUp class="w-4 h-4" />
                                </button>
                                <input
                                    type="number"
                                    :value="time.s"
                                    min="0"
                                    max="59"
                                    class="w-10 h-8 text-center text-sm font-mono border border-slate-200 rounded"
                                    @change="onUnitInput('s', $event)"
                                />
                                <button type="button" class="p-0.5 text-slate-400 hover:text-slate-700" @click="decrement('s')">
                                    <ChevronDown class="w-4 h-4" />
                                </button>
                                <span class="text-xs text-slate-400">{{ __('sec') }}</span>
                            </div>
                        </template>

                        <!-- AM/PM toggle -->
                        <div v-if="is12h" class="flex flex-col gap-1 ml-1">
                            <button
                                type="button"
                                class="px-2 py-1 text-xs font-semibold rounded transition-colors"
                                :class="meridiem === 'AM'
                                    ? 'bg-brand-600 text-white'
                                    : 'bg-slate-100 text-slate-500 hover:bg-slate-200'"
                                @click="meridiem !== 'AM' && toggleMeridiem()"
                            >
                                {{ __('AM') }}
                            </button>
                            <button
                                type="button"
                                class="px-2 py-1 text-xs font-semibold rounded transition-colors"
                                :class="meridiem === 'PM'
                                    ? 'bg-brand-600 text-white'
                                    : 'bg-slate-100 text-slate-500 hover:bg-slate-200'"
                                @click="meridiem !== 'PM' && toggleMeridiem()"
                            >
                                {{ __('PM') }}
                            </button>
                        </div>

                    </div>
                </PopoverContent>
            </Popover>
        </template>
    </FieldWrapper>
</template>
