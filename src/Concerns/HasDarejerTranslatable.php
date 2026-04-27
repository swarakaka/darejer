<?php

namespace Darejer\Concerns;

use Spatie\Translatable\HasTranslations;

/**
 * Combines Spatie's HasTranslations with Darejer's language config so models
 * automatically know about every configured locale without per-model setup.
 *
 *   class Product extends Model
 *   {
 *       use HasDarejerTranslatable;
 *       public array $translatable = ['name', 'description'];
 *   }
 */
trait HasDarejerTranslatable
{
    use HasTranslations;

    public function getDarejerLanguages(): array
    {
        return config('darejer.languages', ['en']);
    }

    /**
     * Returns the field's translation in the current locale, falling back to
     * the default Darejer language, then the first non-empty value.
     */
    public function getTranslationWithFallback(string $field): string
    {
        $locale = app()->getLocale();
        $default = config('darejer.default_language', 'en');

        $value = $this->getTranslation($field, $locale, false);
        if ($value !== '') {
            return $value;
        }

        $value = $this->getTranslation($field, $default, false);
        if ($value !== '') {
            return $value;
        }

        foreach ($this->getTranslations($field) as $val) {
            if ($val !== '') {
                return $val;
            }
        }

        return '';
    }

    /**
     * Bulk-set translations.
     *
     * @param  array<string, string>  $translations
     */
    public function setDarejerTranslations(string $field, array $translations): static
    {
        foreach ($translations as $locale => $value) {
            $this->setTranslation($field, $locale, (string) $value);
        }

        return $this;
    }

    /**
     * Full translations including empty strings for any configured language
     * that doesn't yet have a value — shaped exactly how the Inertia payload
     * expects it so TranslatableInput/TranslatableTextarea can render cleanly.
     */
    public function getFullTranslations(string $field): array
    {
        $languages = $this->getDarejerLanguages();
        $existing = $this->getTranslations($field);
        $full = [];

        foreach ($languages as $locale) {
            $full[$locale] = $existing[$locale] ?? '';
        }

        return $full;
    }
}
