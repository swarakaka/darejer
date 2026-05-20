import { useHttp, router } from '@inertiajs/vue3'
import { shallowReactive, shallowRef, computed } from 'vue'
import { handleHttpException } from '@/lib/handleHttpException'
import type { DarejerComponent } from '@/types/darejer'

export interface DarejerFormOptions {
  url: string
  method: 'post' | 'put' | 'patch' | 'delete'
  components: DarejerComponent[]
  record: Record<string, unknown>
  onSuccess?: (response?: DarejerJsonResponse) => void
  onError?: (errors: Record<string, string>) => void
  dialog?: boolean
}

/**
 * Standard envelope returned by `DarejerController::json*()` helpers.
 * `redirect` is set by `jsonRedirect()` on save-then-navigate flows.
 */
export interface DarejerJsonResponse {
  success?: boolean
  message?: string
  redirect?: string
  data?: unknown
  flash?: unknown
  url?: string
}

// useHttp's runtime shape — index-signature mutable, plus the standard
// useForm-style controls. Casting once here keeps call-sites typed.
type UseHttpInstance = Record<string, unknown> & {
  errors: Record<string, string>
  processing: boolean
  post(url: string, opts?: object): Promise<unknown>
  put(url: string, opts?: object): Promise<unknown>
  patch(url: string, opts?: object): Promise<unknown>
  delete(url: string, opts?: object): Promise<unknown>
}

/**
 * Darejer form composable.
 *
 * Submits via Inertia v3 `useHttp` and expects the controller to return
 * Darejer's JSON envelope (`$this->jsonRedirect()` / `$this->jsonSuccess()`).
 * On success, if `redirect` is set, performs a soft `router.visit()` so the
 * destination Screen mounts fresh — Screen.vue's `syncRecord` watcher then
 * re-binds the form to the freshly-arrived record.
 *
 * Why `useHttp` instead of `useForm`: `useForm` expects an Inertia page
 * response and follows server redirects internally. Pairing it with a JSON
 * envelope worked, but server-side hard navigation via `Inertia::location()`
 * surfaced as a noisy 409 in DevTools. Driving the submit with `useHttp`
 * lets the controller answer with plain JSON and the client decide how to
 * navigate — no protocol-level 409, validation errors still flow through
 * `errors` (422), and file uploads still auto-convert to FormData.
 *
 * Reactivity note: `formData` is `shallowReactive`. Nested translatable
 * bags / repeater rows are stored as plain objects — leaf components own
 * their own deep state and emit whole replacement snapshots. Per-keystroke
 * cost stays at O(1) reactivity triggers.
 */
export function useDarejerForm(options: DarejerFormOptions) {
  function buildInitialData(
    record: Record<string, unknown> = options.record,
    components: DarejerComponent[] = options.components,
  ): Record<string, unknown> {
    const data: Record<string, unknown> = {}

    for (const component of components) {
      const name = component.name
      const value = record[name] ?? component.default ?? null
      data[name] = value
    }

    return data
  }

  let initial = buildInitialData()

  // useHttp owns the wire-level state: field bag, processing flag, error
  // map. We mutate fields by direct assignment (`http[name] = value`).
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const http = useHttp(initial as any) as unknown as UseHttpInstance

  // Shallow-reactive mirror that <Screen> hands to children. Keeping a
  // separate layer means children only re-render when their specific key
  // changes, regardless of what useHttp's internal proxy does.
  const formData = shallowReactive<Record<string, unknown>>({ ...initial })

  // One boolean ref instead of re-iterating field keys per keystroke.
  const isDirty = shallowRef(false)

  function updateField(name: string, value: unknown) {
    formData[name] = value
    http[name] = value

    if (!isDirty.value && value !== initial[name]) {
      isDirty.value = true
    }
  }

  const errors = computed((): Record<string, string> => ({ ...(http.errors ?? {}) }))

  const processing = computed(() => http.processing)

  function submit() {
    // Sync formData → http before dispatch (covers any direct formData
    // mutations that bypassed updateField).
    for (const [key, value] of Object.entries(formData)) {
      http[key] = value
    }

    http[options.method](options.url, {
      preserveScroll: true,
      onSuccess: (response: DarejerJsonResponse) => {
        isDirty.value = false

        if (options.dialog && typeof window !== 'undefined') {
          // Broadcast for parent screens (e.g. a Combobox that opened
          // this dialog) — typically refreshes its data and auto-selects
          // the newly-created record.
          window.dispatchEvent(
            new CustomEvent('darejer:dialog-saved', {
              detail: {
                url: response?.redirect ?? response?.url ?? null,
                flash: response?.flash ?? null,
              },
            }),
          )
          window.history.back()
          options.onSuccess?.(response)
          return
        }

        if (response?.redirect) {
          router.visit(response.redirect)
        }

        options.onSuccess?.(response)
      },
      onError: (errs: Record<string, string>) => {
        options.onError?.(errs)
      },
      onHttpException: (response: { status: number }) => {
        handleHttpException(response)
      },
    })
  }

  /**
   * Re-bind the form to a freshly-arrived record (e.g. after a soft Inertia
   * redirect from create → show — same Screen.vue instance, new props).
   * Without this, formData would still hold the pre-submit values and
   * shadow the new record until a hard refresh.
   */
  function syncRecord(record: Record<string, unknown>, components: DarejerComponent[] = options.components) {
    const next = buildInitialData(record, components)

    for (const key of Object.keys(formData)) delete formData[key]
    Object.assign(formData, next)

    // Reassign every key on the http bag too. useHttp doesn't expose a
    // `defaults()` like useForm, so we set the values directly — that's
    // enough because useHttp clears its own errors on successful submit.
    for (const [k, v] of Object.entries(next)) {
      http[k] = v
    }

    initial = next
    isDirty.value = false
  }

  function cancel(cancelUrl?: string) {
    if (options.dialog && typeof window !== 'undefined') {
      window.history.back()
      return
    }

    if (cancelUrl) {
      router.visit(cancelUrl)
    }
  }

  return {
    formData,
    errors,
    processing,
    isDirty,
    updateField,
    submit,
    syncRecord,
    cancel,
  }
}
