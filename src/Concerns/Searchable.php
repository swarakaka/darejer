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

        return $query->where(function (Builder $q) use ($columns, $like): void {
            $grammar = $q->getQuery()->getGrammar();

            foreach ($columns as $column) {
                $q->orWhereRaw($grammar->wrap($column)." LIKE ? ESCAPE '!'", [$like]);
            }
        });
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

        if ($column === null) {
            return null;
        }

        $value = $this->getAttribute($column);

        return ($value === null || $value === '') ? null : (string) $value;
    }

    /**
     * Optional smaller line under the label (e.g. the record's code).
     */
    public function getSearchableSubtitle(): ?string
    {
        $column = property_exists($this, 'searchableSubtitle') ? $this->searchableSubtitle : null;

        if (! $column) {
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
