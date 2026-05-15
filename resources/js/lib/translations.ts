// Translations are loaded from `lang/<locale>.json` at build time via Vite's
// glob import. The JSON files are flat key->value maps generated from the PHP
// language files (regenerate via `php artisan darejer:lang:export`). New
// locales auto-register when their JSON file is added — no manual import.
//
// `import.meta.glob` requires literal relative/absolute paths in Vite 8+;
// aliases like `@lang` are rejected. The path below resolves from
// `resources/js/lib/translations.ts` → `lang/` at the package root, so it
// works whether this file is in the package itself or imported from a
// host app that has darejer in node_modules.
const modules = import.meta.glob<Record<string, string>>('../../../lang/*.json', {
  eager: true,
  import: 'default',
})

const locales: Record<string, Record<string, string>> = {}

for (const path in modules) {
  const match = path.match(/\/([^/]+)\.json$/)
  if (match) {
    locales[match[1]] = modules[path]
  }
}

export function getTranslations(locale: string): Record<string, string> {
  return locales[locale] ?? {}
}
