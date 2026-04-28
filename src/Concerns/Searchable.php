<?php

declare(strict_types=1);

namespace Darejer\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Str;

/**
 * Mix into any Eloquent model to expose it to Darejer's global search
 * (the topbar quick-jump powered by `Darejer\Support\GlobalSearch`).
 *
 * Models declare which attributes are searched and how a result is
 * rendered:
 *
 *   protected array $searchable = ['code', 'name', 'email'];
 *
 * Optional overrides (defaults are guessed):
 *   protected ?string $searchableLabel     = 'name';        // primary line
 *   protected ?string $searchableSubtitle  = 'code';        // secondary line
 *   protected ?string $searchableRouteName = 'leads.show';  // route('leads.show', $id)
 *   protected ?string $searchableTypeLabel = 'Lead';        // group header / chip
 *
 * Models also need to be registered with `Darejer\Data\ModelRegistry`
 * (typically the host's service provider) so the search controller can
 * discover them — the registry doubles as the searchable-model index.
 *
 * @mixin Model
 */
trait Searchable
{
    /**
     * Eloquent local scope used by `GlobalSearch` to build the LIKE
     * query across `$searchable` columns. Exposed so callers can
     * compose with their own constraints (e.g. company scoping):
     *
     *   Lead::query()->searchableTerm('acme')->where('company_id', 7);
     */
    public function scopeSearchableTerm(Builder $query, string $term): Builder
    {
        $columns = $this->getSearchableAttributes();

        if ($columns === [] || $term === '') {
            return $query;
        }

        // Escape LIKE meta-characters so a user typing `100%` or `user_1`
        // matches the literal string. The escape symbol is `!` rather than
        // backslash because backslash inside a single-quoted SQL literal
        // confuses MySQL/MariaDB (it escapes the closing quote) — `!` is
        // safe across MySQL, MariaDB, PostgreSQL and SQLite.
        $escaped = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term);
        $like = '%'.$escaped.'%';

        $translatable = method_exists($this, 'getTranslatableAttributes')
            ? (array) $this->getTranslatableAttributes()
            : [];

        return $query->where(function (Builder $q) use ($columns, $like, $translatable): void {
            $grammar = $q->getQuery()->getGrammar();

            foreach ($columns as $column) {
                if (in_array($column, $translatable, true)) {
                    // Translatable attributes are stored as JSON
                    // ({"en": "...", "ar": "..."}). Search every locale's
                    // value separately via the grammar's JSON-path wrap
                    // (`column->locale`) so the match is anchored to the
                    // actual translation — and so the SQL stays portable
                    // across MySQL, MariaDB, PostgreSQL and SQLite.
                    foreach ($this->searchableLocalesFor($column) as $locale) {
                        $q->orWhereRaw(
                            $grammar->wrap($column.'->'.$locale)." LIKE ? ESCAPE '!'",
                            [$like]
                        );
                    }

                    continue;
                }

                $q->orWhereRaw($grammar->wrap($column)." LIKE ? ESCAPE '!'", [$like]);
            }
        });
    }

    /**
     * Locales searched on a translatable column. Defaults to the configured
     * Darejer languages so search covers every UI locale; override on the
     * model to scope to a subset.
     *
     * @return list<string>
     */
    protected function searchableLocalesFor(string $column): array
    {
        $configured = config('darejer.languages');

        if (is_array($configured) && $configured !== []) {
            return array_values(array_unique(array_map('strval', $configured)));
        }

        return [app()->getLocale(), (string) config('app.fallback_locale', 'en')];
    }

    /**
     * Columns scanned by the LIKE search. Empty list disables the model
     * from search results without removing the trait.
     *
     * @return list<string>
     */
    public function getSearchableAttributes(): array
    {
        return property_exists($this, 'searchable') && is_array($this->searchable)
            ? array_values($this->searchable)
            : [];
    }

    /**
     * Primary line shown for this record in the search dropdown.
     */
    public function getSearchableLabel(): ?string
    {
        $column = (property_exists($this, 'searchableLabel') && $this->searchableLabel)
            ? $this->searchableLabel
            : $this->guessSearchableLabelColumn();

        return $column === null ? null : $this->resolveSearchableValue($column);
    }

    /**
     * Optional smaller line under the label (e.g. the record's code).
     */
    public function getSearchableSubtitle(): ?string
    {
        $column = property_exists($this, 'searchableSubtitle') ? $this->searchableSubtitle : null;

        return $column ? $this->resolveSearchableValue($column) : null;
    }

    /**
     * Read a column value, with translatable-attribute awareness so a
     * record that only carries an `ar` translation still renders with
     * that text when the active locale is `en` (and vice versa).
     */
    private function resolveSearchableValue(string $column): ?string
    {
        if (method_exists($this, 'isTranslatableAttribute') && $this->isTranslatableAttribute($column)) {
            // Spatie returns '' for an empty/missing locale once fallback
            // is exhausted, so we have to walk the translations bag
            // ourselves to surface a value from any locale that has one.
            $translations = method_exists($this, 'getTranslations')
                ? (array) $this->getTranslations($column)
                : [];

            foreach ([app()->getLocale(), config('app.fallback_locale'), config('darejer.default_language', 'en')] as $locale) {
                if ($locale && ! empty($translations[$locale])) {
                    return (string) $translations[$locale];
                }
            }

            foreach ($translations as $value) {
                if ($value !== null && $value !== '') {
                    return (string) $value;
                }
            }

            return null;
        }

        $value = $this->getAttribute($column);

        return ($value === null || $value === '') ? null : (string) $value;
    }

    /**
     * Group header / chip label — humanized class basename by default.
     */
    public function getSearchableTypeLabel(): string
    {
        if (property_exists($this, 'searchableTypeLabel') && $this->searchableTypeLabel) {
            return $this->searchableTypeLabel;
        }

        return Str::headline(class_basename(static::class));
    }

    /**
     * URL the dropdown navigates to when the user picks this record.
     * Resolves the model's `show` route by default.
     */
    public function getSearchableUrl(): ?string
    {
        $name = (property_exists($this, 'searchableRouteName') && $this->searchableRouteName)
            ? $this->searchableRouteName
            : $this->guessSearchableRouteName();

        if (! $name || ! RouteFacade::has($name)) {
            return null;
        }

        try {
            return route($name, $this->getKey());
        } catch (\Throwable) {
            return null;
        }
    }

    private function guessSearchableLabelColumn(): ?string
    {
        $attributes = $this->getAttributes();
        $searchable = $this->getSearchableAttributes();

        foreach (['name', 'full_name', 'title', 'code', 'email'] as $candidate) {
            if (array_key_exists($candidate, $attributes) || in_array($candidate, $searchable, true)) {
                return $candidate;
            }
        }

        return $searchable[0] ?? null;
    }

    private function guessSearchableRouteName(): ?string
    {
        $base = Str::kebab(Str::pluralStudly(class_basename(static::class)));

        return "{$base}.show";
    }
}
