import { ref, computed } from 'vue'

const STORAGE_KEY = 'darejer-sidebar-collapsed'
const MOBILE_QUERY = '(max-width: 767px)'

const mobileOpen = ref(false)
const collapsed  = ref(false)
const isMobile   = ref(false)

let initialized = false
let mediaQuery: MediaQueryList | null = null

function syncMobile(matches: boolean) {
    isMobile.value = matches
    if (!matches) {
        mobileOpen.value = false
    }
}

function init() {
    if (initialized || typeof window === 'undefined') return
    initialized = true

    const stored = localStorage.getItem(STORAGE_KEY)
    if (stored !== null) {
        collapsed.value = stored === '1'
    }

    if (typeof window.matchMedia === 'function') {
        mediaQuery = window.matchMedia(MOBILE_QUERY)
        syncMobile(mediaQuery.matches)
        mediaQuery.addEventListener('change', (e) => syncMobile(e.matches))
    }
}

// On mobile we always render the expanded sidebar — the desktop "collapsed"
// preference is irrelevant inside an off-canvas drawer.
const effectiveCollapsed = computed(() => !isMobile.value && collapsed.value)

export function useSidebar() {
    init()

    function openMobile() {
        mobileOpen.value = true
    }

    function closeMobile() {
        mobileOpen.value = false
    }

    function toggleMobile() {
        mobileOpen.value = !mobileOpen.value
    }

    function toggleCollapsed() {
        collapsed.value = !collapsed.value
        if (typeof window !== 'undefined') {
            localStorage.setItem(STORAGE_KEY, collapsed.value ? '1' : '0')
        }
    }

    return {
        mobileOpen,
        collapsed,
        effectiveCollapsed,
        isMobile,
        openMobile,
        closeMobile,
        toggleMobile,
        toggleCollapsed,
    }
}
