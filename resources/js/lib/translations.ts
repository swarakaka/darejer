// Translations are loaded from `lang/<locale>.json` at build time via Vite's
// glob import. The JSON files are flat key->value maps generated from the PHP
// language files (regenerate via `php artisan darejer:lang:export`). New
// locales auto-register when their JSON file is added — no manual import.
const modules = import.meta.glob<Record<string, string>>('@lang/*.json', {
  eager: true,
  import: 'default',
});

const locales: Record<string, Record<string, string>> = {};

for (const path in modules) {
  const match = path.match(/\/([^/]+)\.json$/);
  if (match) {
    locales[match[1]] = modules[path];
  }
}

export function getTranslations(locale: string): Record<string, string> {
  return locales[locale] ?? {};
}
