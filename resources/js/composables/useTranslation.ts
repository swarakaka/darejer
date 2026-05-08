import { usePage } from '@inertiajs/vue3'
import { getTranslations } from '@/lib/translations'
import { computed } from 'vue'

interface DarejerShare {
  locale: string
  default_language: string
  languages: string[]
  direction: string
  is_rtl: boolean
  directions: Record<string, string>
  translations?: Record<string, string>
}

interface SharedProps extends Record<string, unknown> {
  darejer: DarejerShare
}

const warnedKeys = new Set<string>()

const useTranslation = () => {
  const __ = (key: string, replace: Record<string, string | number | null> = {}) => {
    const share = computed(() => usePage<SharedProps>().props.darejer)
    const locale = computed(() => share.value?.locale ?? 'en')
    const translations = getTranslations(locale.value)
    const hostTranslations = share.value?.translations ?? null

    const raw =
      (hostTranslations && hostTranslations[key] !== undefined ? hostTranslations[key] : null) ??
      translations?.[key] ??
      null

    let translation = raw && raw !== '__MISSING__' ? raw : key

    if (import.meta.env.DEV && (!raw || raw === '__MISSING__')) {
      if (locale.value !== 'en' && !warnedKeys.has(`${locale.value}:${key}`)) {
        warnedKeys.add(`${locale.value}:${key}`)
        console.warn(`[i18n] Missing translation for key: "${key}" in locale: ${locale.value}`)
      }
    }

    Object.keys(replace).forEach(function (rKey) {
      if (replace[rKey] === null) {
        translation = translation.replace(new RegExp(':' + rKey, 'g'), 'null')
      } else {
        translation = translation.replace(':' + rKey, String(replace[rKey]))
      }
    })

    return translation
  }

  const __n = (
    key: string,
    number: number,
    replace: Record<string, string | number | null> = {},
  ) => {
    const options = key.split('|')

    const resolved = number === 1 ? options[0] : options[1]

    return __(resolved, replace)
  }

  /**
   * Resolve a translatable value against the active locale.
   *
   * Accepts the three forms a HasTranslations attribute can arrive in:
   *   1. A pre-parsed dict: `{ en: 'X', ar: 'س' }` (Inertia serializes
   *      Eloquent `HasTranslations` casts this way).
   *   2. A JSON string: `'{"en":"X","ar":"س"}'` (raw DB column, untranslated
   *      ColumnFormatters, etc.).
   *   3. A plain string: returned as-is.
   *
   * Fallback order: requested locale → default language → first non-empty
   * value → empty string.
   */
  const resolveTranslatable = (value: unknown): string => {
    if (value === null || value === undefined) {
      return ''
    }

    const pickFromDict = (dict: Record<string, unknown>): string | null => {
      const page = usePage<SharedProps>()
      const currentLocale = page.props.darejer?.locale ?? 'en'
      const defaultLang = page.props.darejer?.default_language ?? 'en'

      const at = (k: string) => (typeof dict[k] === 'string' ? (dict[k] as string) : '')
      if (at(currentLocale) !== '') return at(currentLocale)
      if (at(defaultLang) !== '') return at(defaultLang)
      const firstFilled = Object.values(dict).find((v) => typeof v === 'string' && v !== '')
      return typeof firstFilled === 'string' ? firstFilled : null
    }

    // Pre-parsed dict (most common path: HasTranslations through Inertia).
    if (typeof value === 'object' && !Array.isArray(value)) {
      return pickFromDict(value as Record<string, unknown>) ?? ''
    }

    if (typeof value !== 'string') {
      return String(value)
    }

    // JSON-encoded dict (raw DB column or API response that didn't decode).
    const trimmed = value.trim()
    if (trimmed.startsWith('{') && trimmed.endsWith('}')) {
      try {
        const parsed = JSON.parse(trimmed)
        if (parsed && typeof parsed === 'object' && !Array.isArray(parsed)) {
          const picked = pickFromDict(parsed as Record<string, unknown>)
          if (picked !== null) return picked
        }
      } catch {
        // Not valid JSON — fall through and return the original string.
      }
    }

    return value
  }

  return {
    __,
    __n,
    resolveTranslatable,
  }
}

export default useTranslation
