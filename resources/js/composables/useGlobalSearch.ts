import { ref, computed } from 'vue'
import { useHttp } from '@inertiajs/vue3'

/**
 * Topbar quick-jump backed by `Darejer\Http\Controllers\SearchController`.
 *
 * Always goes through `useHttp` per project convention — never `fetch()`
 * or `axios`. The composable owns its own debounce so callers just feed
 * it the raw `q` string on every keystroke.
 */

export interface SearchItem {
    id:       number | string
    label:    string
    subtitle: string | null
    url:      string | null
}

export interface SearchGroup {
    slug:  string
    type:  string
    items: SearchItem[]
}

interface SearchResponse {
    query:  string
    groups: SearchGroup[]
    total:  number
}

const groups  = ref<SearchGroup[]>([])
const total   = ref(0)
const loading = ref(false)
const lastQ   = ref('')
let   debounceId: ReturnType<typeof setTimeout> | null = null
let   inflight: number = 0

export function useGlobalSearch() {
    const http = useHttp<Record<string, never>, SearchResponse & Record<string, unknown>>()

    const hasResults = computed(() => total.value > 0)

    function reset(): void {
        groups.value = []
        total.value  = 0
        lastQ.value  = ''
        loading.value = false
    }

    function search(q: string, debounceMs = 200): void {
        const term = q.trim()

        if (debounceId) clearTimeout(debounceId)

        if (term === '') {
            reset()
            return
        }

        loading.value = true

        debounceId = setTimeout(() => {
            const ticket = ++inflight

            http.get(`${route('darejer.search').toString()}?q=${encodeURIComponent(term)}`, {
                onSuccess: (data) => {
                    if (ticket !== inflight) return
                    groups.value = (data?.groups as SearchGroup[]) ?? []
                    total.value  = (data?.total  as number)        ?? 0
                    lastQ.value  = term
                },
            }).then(() => undefined, () => undefined).finally(() => {
                if (ticket === inflight) loading.value = false
            })
        }, debounceMs)
    }

    return { groups, total, loading, lastQ, hasResults, search, reset }
}
