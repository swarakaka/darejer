import { ref, computed } from 'vue'

const STORAGE_KEY = 'darejer-sidebar-collapsed'
const EXPANDED_GROUPS_KEY = 'darejer-sidebar-expanded-groups'
const MOBILE_QUERY = '(max-width: 767px)'

const mobileOpen = ref(false)
const collapsed = ref(false)
const isMobile = ref(false)
const expandedGroups = ref<Set<string>>(new Set())

let initialized = false
let mediaQuery: MediaQueryList | null = null

function syncMobile(matches: boolean) {
  isMobile.value = matches
  if (!matches) {
    mobileOpen.value = false
  }
}

function persistExpandedGroups() {
  if (typeof window === 'undefined') return
  localStorage.setItem(EXPANDED_GROUPS_KEY, JSON.stringify([...expandedGroups.value]))
}

function init() {
  if (initialized || typeof window === 'undefined') return
  initialized = true

  const stored = localStorage.getItem(STORAGE_KEY)
  if (stored !== null) {
    collapsed.value = stored === '1'
  }

  const storedGroups = localStorage.getItem(EXPANDED_GROUPS_KEY)
  if (storedGroups !== null) {
    try {
      const parsed = JSON.parse(storedGroups)
      if (Array.isArray(parsed)) {
        expandedGroups.value = new Set(parsed.filter((k): k is string => typeof k === 'string'))
      }
    } catch {
      // ignore malformed payload
    }
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

  function isGroupExpanded(key: string): boolean {
    return expandedGroups.value.has(key)
  }

  function toggleGroup(key: string, exclusive = true) {
    if (expandedGroups.value.has(key)) {
      expandedGroups.value.delete(key)
    } else {
      if (exclusive) expandedGroups.value.clear()
      expandedGroups.value.add(key)
    }
    persistExpandedGroups()
  }

  function expandGroup(key: string) {
    if (expandedGroups.value.has(key)) return
    expandedGroups.value.add(key)
    persistExpandedGroups()
  }

  return {
    mobileOpen,
    collapsed,
    effectiveCollapsed,
    isMobile,
    expandedGroups,
    openMobile,
    closeMobile,
    toggleMobile,
    toggleCollapsed,
    isGroupExpanded,
    toggleGroup,
    expandGroup,
  }
}
