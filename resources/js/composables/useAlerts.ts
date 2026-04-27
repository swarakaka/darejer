import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useHttp, usePage } from '@inertiajs/vue3'
import type { DarejerSharedProps } from '@/types/darejer'

/**
 * Per-user notification feed used by the topbar Bell + slideover.
 *
 * Initial state (count + first page) is fetched via Inertia `useHttp` —
 * never `fetch()` / `axios`, per project convention. Live updates arrive
 * over Laravel Reverb on the private `darejer.alerts.{userId}` channel:
 * if `window.Echo` is present we subscribe; otherwise the feed still
 * works (just no real-time push, manual refresh on slideover open).
 */

export type AlertLevel = 'success' | 'error' | 'warning' | 'info'

export interface AlertRecord {
    id:         number
    level:      AlertLevel
    message:    string
    link:       string | null
    data:       Record<string, unknown> | null
    read_at:    string | null
    created_at: string | null
}

interface AlertsResponse {
    data: AlertRecord[]
    meta: { unread_count: number; total: number; current_page: number; last_page: number; per_page: number }
}

interface CountResponse { unread_count: number }
interface AckResponse   { success: boolean; data?: AlertRecord }

interface EchoLike {
    private(channel: string): {
        listen(event: string, cb: (payload: { alert: AlertRecord }) => void): unknown
    }
    leave(channel: string): void
}

declare global {
    interface Window { Echo?: EchoLike }
}

const items       = ref<AlertRecord[]>([])
const unreadCount = ref(0)
const loading     = ref(false)
const loaded      = ref(false)
let   subscribed  = false

export function useAlerts() {
    const page = usePage<DarejerSharedProps>()

    const userId = computed<number | null>(
        () => (page.props.auth?.user?.id as number | undefined) ?? null,
    )

    const hasUnread = computed(() => unreadCount.value > 0)

    const listHttp  = useHttp<Record<string, never>, AlertsResponse & Record<string, unknown>>()
    const countHttp = useHttp<Record<string, never>, CountResponse & Record<string, unknown>>()
    const ackHttp   = useHttp<Record<string, never>, AckResponse  & Record<string, unknown>>()

    function refreshCount(): Promise<void> {
        return countHttp.get(route('darejer.alerts.count').toString(), {
            onSuccess: (data) => {
                if (data && typeof data.unread_count === 'number') {
                    unreadCount.value = data.unread_count
                }
            },
        }).then(() => undefined, () => undefined)
    }

    function loadList(): Promise<void> {
        loading.value = true
        return listHttp.get(route('darejer.alerts.index').toString(), {
            onSuccess: (data) => {
                items.value       = (data?.data as AlertRecord[]) ?? []
                unreadCount.value = (data?.meta as AlertsResponse['meta'] | undefined)?.unread_count ?? 0
                loaded.value      = true
            },
        })
            .then(() => undefined, () => undefined)
            .finally(() => { loading.value = false })
    }

    function markRead(id: number): Promise<void> {
        return ackHttp.post(route('darejer.alerts.read', { id }).toString(), {
            onSuccess: (data) => {
                const updated = data?.data
                const idx = items.value.findIndex(a => a.id === id)
                if (idx !== -1) {
                    items.value[idx] = updated ?? { ...items.value[idx], read_at: new Date().toISOString() }
                }
                if (unreadCount.value > 0) unreadCount.value--
            },
        }).then(() => undefined, () => undefined)
    }

    function markAllRead(): Promise<void> {
        return ackHttp.post(route('darejer.alerts.read-all').toString(), {
            onSuccess: () => {
                const now = new Date().toISOString()
                items.value = items.value.map(a => a.read_at ? a : { ...a, read_at: now })
                unreadCount.value = 0
            },
        }).then(() => undefined, () => undefined)
    }

    function destroy(id: number): Promise<void> {
        return ackHttp.delete(route('darejer.alerts.destroy', { id }).toString(), {
            onSuccess: () => {
                const removed = items.value.find(a => a.id === id)
                items.value = items.value.filter(a => a.id !== id)
                if (removed && removed.read_at === null && unreadCount.value > 0) {
                    unreadCount.value--
                }
            },
        }).then(() => undefined, () => undefined)
    }

    function clearAll(): Promise<void> {
        return ackHttp.delete(route('darejer.alerts.clear').toString(), {
            onSuccess: () => {
                items.value = []
                unreadCount.value = 0
            },
        }).then(() => undefined, () => undefined)
    }

    function subscribe(): void {
        if (subscribed) return
        const echo = typeof window !== 'undefined' ? window.Echo : undefined
        const id   = userId.value
        if (!echo || id === null) return

        echo
            .private(`darejer.alerts.${id}`)
            .listen('.alert.created', (payload) => {
                if (!payload?.alert) return
                items.value = [payload.alert, ...items.value]
                if (payload.alert.read_at === null) unreadCount.value++
            })

        subscribed = true
    }

    function unsubscribe(): void {
        if (!subscribed) return
        const echo = typeof window !== 'undefined' ? window.Echo : undefined
        const id   = userId.value
        if (echo && id !== null) {
            echo.leave(`darejer.alerts.${id}`)
        }
        subscribed = false
    }

    onMounted(() => {
        if (userId.value === null) return
        // Hydrate the bell badge ASAP. The slideover loads the full list
        // lazily (on first open) — see AppNotifications.vue.
        if (!loaded.value && unreadCount.value === 0) {
            void refreshCount()
        }
        subscribe()
    })

    onBeforeUnmount(() => {
        // Don't tear the subscription down on every component unmount —
        // the topbar Bell stays mounted for the whole session, and the
        // slideover sharing the same composable should not lose pushes
        // when it closes. Leave subscribe lifecycle tied to login/logout.
    })

    return {
        items,
        unreadCount,
        hasUnread,
        loading,
        loaded,
        loadList,
        refreshCount,
        markRead,
        markAllRead,
        destroy,
        clearAll,
        subscribe,
        unsubscribe,
    }
}
