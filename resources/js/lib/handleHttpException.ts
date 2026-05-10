import { router } from '@inertiajs/vue3'

/**
 * Shared handler for Inertia v3 `useHttp` `onHttpException` callbacks.
 *
 * The most common non-2xx response we want to react to globally is a 401
 * — the user's session has expired (or they were never authenticated for
 * this endpoint) and the controller is returning JSON instead of the HTML
 * login page that a router visit would have followed automatically.
 *
 * Returns `true` when the response was handled here so call sites can
 * skip their own fallback logic (toast, retry, etc).
 */
export function handleHttpException(response: { status: number } | null | undefined): boolean {
  if (!response) return false

  if (response.status === 401) {
    redirectToLogin()
    return true
  }

  return false
}

let redirecting = false

function redirectToLogin(): void {
  // Guard against several useHttp calls firing in parallel and each
  // queueing their own visit — only the first 401 in a tick should win.
  if (redirecting) return
  redirecting = true

  const target = resolveLoginUrl()
  router.visit(target, { replace: true })
}

function resolveLoginUrl(): string {
  // Prefer the named Fortify route via Ziggy when available; fall back to
  // a sensible literal so this helper still works in contexts where the
  // route() global isn't injected (e.g. early bootstrap).
  const ziggy = (globalThis as { route?: (name: string) => { toString(): string } }).route
  try {
    if (typeof ziggy === 'function') {
      return ziggy('login').toString()
    }
  } catch {
    // route() throws when the named route isn't registered — fall through.
  }
  return '/login'
}
