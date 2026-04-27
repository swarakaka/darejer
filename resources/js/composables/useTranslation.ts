import { usePage } from '@inertiajs/vue3';
import { getTranslations } from '@/lib/translations';
import { computed } from 'vue';

interface DarejerShare {
  locale: string;
  default_language: string;
  languages: string[];
  direction: string;
  is_rtl: boolean;
  directions: Record<string, string>;
}

interface SharedProps extends Record<string, unknown> {
  darejer: DarejerShare;
}

const warnedKeys = new Set<string>();

const useTranslation = () => {
  const __ = (key: string, replace: Record<string, string | number | null> = {}) => {
    const locale = computed(() => usePage<SharedProps>().props.darejer?.locale ?? 'en');
    const translations = getTranslations(locale.value);

    const raw = translations?.[key] ?? null;

    let translation = raw && raw !== '__MISSING__' ? raw : key;

    if (import.meta.env.DEV && (!raw || raw === '__MISSING__')) {
      if (locale.value !== 'en' && !warnedKeys.has(`${locale.value}:${key}`)) {
        warnedKeys.add(`${locale.value}:${key}`);
        console.warn(`[i18n] Missing translation for key: "${key}" in locale: ${locale.value}`);
      }
    }

    Object.keys(replace).forEach(function (rKey) {
      if (replace[rKey] === null) {
        translation = translation.replace(new RegExp(':' + rKey, 'g'), 'null');
      } else {
        translation = translation.replace(':' + rKey, String(replace[rKey]));
      }
    });

    return translation;
  };

  const __n = (key: string, number: number, replace: Record<string, string | number | null> = {}) => {
    const options = key.split('|');

    const resolved = number === 1 ? options[0] : options[1];

    return __(resolved, replace);
  };

  return {
    __,
    __n,
  };
};

export default useTranslation;
