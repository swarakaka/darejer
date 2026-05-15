import { ref, computed } from 'vue'

const STORAGE_KEY = 'darejer-sidebar-collapsed'
const EXPANDED_GROUP_KEY = 'darejer-sidebar-expanded-group'
const LEGACY_EXPANDED_GROUPS_KEY = 'darejer-sidebar-expanded-groups'
const MOBILE_QUERY = '(max-width: 767px)'

const mobileOpen = ref(false)
const collapsed = ref(false)
const isMobile = ref(false)
const expandedGroup = ref<string | null>(null)

let initialized = false
let mediaQuery: MediaQueryList | null = null

function syncMobile(matches: boolean) {
  isMobile.value = matches
  if (!matches) {
    mobileOpen.value = false
  }
}

function persistExpandedGroup() {
  if (typeof window === 'undefined') return
  if (expandedGroup.value === null) {
    localStorage.removeItem(EXPANDED_GROUP_KEY)
  } else {
    localStorage.setItem(EXPANDED_GROUP_KEY, expandedGroup.value)
  }
}

function init() {
  if (initialized || typeof window === 'undefined') return
  initialized = true

  const stored = localStorage.getItem(STORAGE_KEY)
  if (stored !== null) {
    collapsed.value = stored === '1'
  }

  const storedGroup = localStorage.getItem(EXPANDED_GROUP_KEY)
  if (storedGroup !== null) {
    expandedGroup.value = storedGroup
  } else {
    // Migrate from the legacy multi-group format (a JSON-encoded string array)
    // by adopting its first entry, then dropping the old key.
    const legacy = localStorage.getItem(LEGACY_EXPANDED_GROUPS_KEY)
    if (legacy !== null) {
      try {
        const parsed = JSON.parse(legacy)
        if (Array.isArray(parsed) && typeof parsed[0] === 'string') {
          expandedGroup.value = parsed[0]
          persistExpandedGroup()
        }
      } catch {
        // ignore malformed payload
      }
      localStorage.removeItem(LEGACY_EXPANDED_GROUPS_KEY)
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
    return expandedGroup.value === key
  }

  function toggleGroup(key: string) {
    expandedGroup.value = expandedGroup.value === key ? null : key
    persistExpandedGroup()
  }

  function expandGroup(key: string) {
    if (expandedGroup.value === key) return
    expandedGroup.value = key
    persistExpandedGroup()
  }

  return {
    mobileOpen,
    collapsed,
    effectiveCollapsed,
    isMobile,
    expandedGroup,
    openMobile,
    closeMobile,
    toggleMobile,
    toggleCollapsed,
    isGroupExpanded,
    toggleGroup,
    expandGroup,
  }
}
