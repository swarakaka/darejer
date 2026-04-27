import { ref }     from 'vue'
import { useHttp }  from '@inertiajs/vue3'

export interface DataUrlOptions {
    perPage?:     number
    keyField?:    string
    labelField?:  string
    combobox?:    boolean
    tree?:        boolean
    parentField?: string
    sort?:        string
    order?:       'asc' | 'desc'
    search?:      string
    page?:        number
    filters?:     Record<string, string>
    fields?:      string[]
    with?:        string[]
}

export interface DataUrlResult<T = Record<string, unknown>> {
    data:         T[]
    total:        number
    current_page: number
    last_page:    number
    per_page:     number
    from:         number
    to:           number
}

/**
 * Shared composable for every Darejer component that fetches from a dataUrl.
 * Always goes through `useHttp` — never `fetch()` or `axios`.
 *
 * Options passed at construction time become defaults; `load(overrides)`
 * merges per-call overrides so callers can just re-call with the latest
 * reactive search / sort / filter / page values.
 */
export function useDataUrl<T = Record<string, unknown>>(
    dataUrl: string | undefined,
    defaults: DataUrlOptions = {},
) {
    const http = useHttp<Record<string, never>, DataUrlResult<T> & Record<string, unknown>>()

    const result = ref<DataUrlResult<T>>({
        data:         [],
        total:        0,
        current_page: 1,
        last_page:    1,
        per_page:     defaults.perPage ?? 15,
        from:         0,
        to:           0,
    }) as { value: DataUrlResult<T> }

    function buildParams(options: DataUrlOptions): URLSearchParams {
        const params = new URLSearchParams()

        if (options.perPage)     params.set('per_page',     String(options.perPage))
        if (options.keyField)    params.set('key',          options.keyField)
        if (options.labelField)  params.set('label',        options.labelField)
        if (options.combobox)    params.set('combobox',     '1')
        if (options.tree)        params.set('tree',         '1')
        if (options.parentField) params.set('parent_field', options.parentField)
        if (options.sort)        params.set('sort',         options.sort)
        if (options.order)       params.set('order',        options.order)
        if (options.search)      params.set('search',       options.search)
        if (options.page)        params.set('page',         String(options.page))

        for (const field of options.fields ?? []) {
            params.append('fields[]', field)
        }
        for (const rel of options.with ?? []) {
            params.append('with[]', rel)
        }
        if (options.filters) {
            for (const [field, value] of Object.entries(options.filters)) {
                if (value !== '' && value !== null && value !== undefined) {
                    params.set(`filters[${field}]`, value)
                }
            }
        }

        return params
    }

    function load(overrides: DataUrlOptions = {}): Promise<DataUrlResult<T> | null> {
        if (!dataUrl) return Promise.resolve(null)

        const merged = { ...defaults, ...overrides }
        const params = buildParams(merged)
        const url    = `${dataUrl}?${params}`

        return http.get(url, {
            onSuccess: (data) => {
                result.value = {
                    data:         ((data?.data as T[]) ?? []),
                    total:        (data?.total        as number | undefined) ?? 0,
                    current_page: (data?.current_page as number | undefined) ?? 1,
                    last_page:    (data?.last_page    as number | undefined) ?? 1,
                    per_page:     (data?.per_page     as number | undefined) ?? (merged.perPage ?? 15),
                    from:         (data?.from         as number | undefined) ?? 0,
                    to:           (data?.to           as number | undefined) ?? 0,
                }
            },
        }).then(() => result.value, () => null)
    }

    return { result, load, http }
}
