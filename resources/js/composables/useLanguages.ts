import { computed }     from 'vue'
import { usePage }      from '@inertiajs/vue3'
import type { DarejerSharedProps } from '@/types/darejer'

export function useLanguages() {
    const page = usePage<DarejerSharedProps>()

    const languages = computed(() =>
        page.props.darejer?.languages ?? ['en']
    )

    const defaultLanguage = computed(() =>
        page.props.darejer?.default_language ?? languages.value[0]
    )

    // The user's currently active locale (set by Darejer's locale switcher
    // and reflected in `app()->getLocale()` server-side). Translatable
    // inputs bind to this so a user browsing in `en` sees and edits the
    // English translation first, not the AR/system-default one.
    const currentLocale = computed(() =>
        page.props.darejer?.locale ?? defaultLanguage.value
    )

    const isMultilingual = computed(() => languages.value.length > 1)

    function localeLabel(locale: string): string {
        return locale.split('-')[0].toUpperCase()
    }

    function localeName(locale: string): string {
        if (locale === 'ckb') {
             return 'کوردی'
         }
        try {
            return new Intl.DisplayNames([locale], { type: 'language' }).of(locale) ?? locale
        } catch {
            return locale
        }
    }

    function parseTranslatable(value: unknown): Record<string, string> {
        if (value && typeof value === 'object' && !Array.isArray(value)) {
            return value as Record<string, string>
        }
        if (typeof value === 'string') {
            return { [defaultLanguage.value]: value }
        }
        return Object.fromEntries(languages.value.map(l => [l, '']))
    }

    return {
        languages,
        defaultLanguage,
        currentLocale,
        isMultilingual,
        localeLabel,
        localeName,
        parseTranslatable,
    }
}
