import { ref, computed, watch } from 'vue'

export type ThemeMode = 'light' | 'dark' | 'system'

const STORAGE_KEY = 'darejer-theme'
const COLOR_SCHEME_QUERY = '(prefers-color-scheme: dark)'

const mode         = ref<ThemeMode>('system')
const systemIsDark = ref(false)

let initialized = false
let mediaQuery: MediaQueryList | null = null

function readStored(): ThemeMode {
    if (typeof window === 'undefined') return 'system'
    const stored = localStorage.getItem(STORAGE_KEY)
    return stored === 'light' || stored === 'dark' || stored === 'system' ? stored : 'system'
}

function applyTheme(next: ThemeMode, systemDark: boolean) {
    if (typeof document === 'undefined') return
    const dark = next === 'dark' || (next === 'system' && systemDark)
    document.documentElement.classList.toggle('dark', dark)
    document.documentElement.style.colorScheme = dark ? 'dark' : 'light'
}

function init() {
    if (initialized || typeof window === 'undefined') return
    initialized = true

    mode.value = readStored()

    if (typeof window.matchMedia === 'function') {
        mediaQuery = window.matchMedia(COLOR_SCHEME_QUERY)
        systemIsDark.value = mediaQuery.matches
        mediaQuery.addEventListener('change', (e) => {
            systemIsDark.value = e.matches
        })
    }

    applyTheme(mode.value, systemIsDark.value)

    watch([mode, systemIsDark], ([m, d]) => applyTheme(m, d))
}

// Apply ASAP — runs at module import time, before the app mounts, to minimise flash.
init()

const isDark = computed(
    () => mode.value === 'dark' || (mode.value === 'system' && systemIsDark.value),
)

export function useTheme() {
    function setMode(next: ThemeMode) {
        mode.value = next
        if (typeof window !== 'undefined') {
            localStorage.setItem(STORAGE_KEY, next)
        }
    }

    return {
        mode,
        isDark,
        setMode,
    }
}
