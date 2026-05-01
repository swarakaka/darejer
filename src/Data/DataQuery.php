<?php

namespace Darejer\Data;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Applies request query params to an Eloquent builder in a safe, ordered way.
 */
class DataQuery
{
    protected Builder $query;

    protected Request $request;

    protected string $modelClass;

    public function __construct(Builder $query, Request $request, string $modelClass)
    {
        $this->query = $query;
        $this->request = $request;
        $this->modelClass = $modelClass;
    }

    public function apply(): Builder
    {
        $this
            ->applyDarejerScope()
            ->applySearch()
            ->applyFilters()
            ->applyRelations()
            ->applyFieldSelection()
            ->applySorting();

        return $this->query;
    }

    /**
     * Models can define `public function scopeDarejerQuery(Builder $q)` to
     * apply a default scope (tenancy, soft-delete filters, etc.).
     */
    protected function applyDarejerScope(): static
    {
        if (method_exists($this->modelClass, 'scopeDarejerQuery')) {
            $this->query->darejerQuery();
        }

        return $this;
    }

    protected function applySearch(): static
    {
        $search = $this->request->get('search', '');

        if ($search === '' || $search === null) {
            return $this;
        }

        // Resolve which columns to search across. Priority:
        //   1. ?search_fields[]= explicit list
        //   2. ?label_fields[]=  the multi-field label list
        //   3. ?label=           single-field fallback (legacy default)
        $candidates = $this->request->get('search_fields')
            ?? $this->request->get('label_fields')
            ?? [$this->request->get('label', 'name')];

        if (! is_array($candidates)) {
            $candidates = [$candidates];
        }

        // Sanitize: only [a-zA-Z_][a-zA-Z0-9_]* column names.
        $fields = array_values(array_filter(
            $candidates,
            fn ($f) => is_string($f) && preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $f),
        ));

        if (! $fields) {
            return $this;
        }

        $translatable = method_exists($this->modelClass, 'getTranslatableAttributes')
            ? (new $this->modelClass)->getTranslatableAttributes()
            : [];

        $locale = app()->getLocale();
        $fallback = config('darejer.default_language', 'en');
        $needle = "%{$search}%";

        $this->query->where(function ($q) use ($fields, $translatable, $locale, $fallback, $needle) {
            foreach ($fields as $field) {
                if (in_array($field, $translatable, true)) {
                    // Spatie translatable stores `{ "en": "...", "ar": "..." }`.
                    // Use Eloquent's `column->key` JSON path so the SQL is
                    // generated correctly per driver (MySQL JSON_EXTRACT,
                    // SQLite json_extract, Postgres ->>). Search both the
                    // active locale and the configured fallback so seed
                    // data in English remains findable from other locales.
                    $q->orWhere($field.'->'.$locale, 'like', $needle);
                    if ($fallback !== $locale) {
                        $q->orWhere($field.'->'.$fallback, 'like', $needle);
                    }
                } else {
                    $q->orWhere($field, 'like', $needle);
                }
            }
        });

        return $this;
    }

    /**
     * ?filters[status]=active — restricted to the model's fillable columns.
     */
    protected function applyFilters(): static
    {
        $filters = $this->request->get('filters', []);
        if (! is_array($filters)) {
            return $this;
        }

        $instance = new $this->modelClass;
        $fillable = $instance->getFillable();
        $guarded = $instance->getGuarded();

        foreach ($filters as $field => $value) {
            if (! is_string($field) || ! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $field)) {
                continue;
            }

            if (! empty($fillable) && ! in_array($field, $fillable, true)) {
                continue;
            }

            if ($guarded !== ['*'] && in_array($field, $guarded, true)) {
                continue;
            }

            if ($value === '' || $value === null) {
                continue;
            }

            if (in_array($value, ['true', 'false', '1', '0'], true)) {
                $this->query->where($field, filter_var($value, FILTER_VALIDATE_BOOLEAN));
            } else {
                $this->query->where($field, 'like', "%{$value}%");
            }
        }

        return $this;
    }

    /**
     * ?with[]=relation — only whitelisted relations from
     * `darejerAllowedRelations()` are eagerly loaded.
     */
    protected function applyRelations(): static
    {
        $with = $this->request->get('with', []);
        if (empty($with) || ! is_array($with)) {
            return $this;
        }

        $allowed = method_exists($this->modelClass, 'darejerAllowedRelations')
            ? (new $this->modelClass)->darejerAllowedRelations()
            : [];

        $safe = array_values(array_intersect($with, $allowed));

        if ($safe) {
            $this->query->with($safe);
        }

        return $this;
    }

    /**
     * ?fields[]=id&fields[]=name — sanitized column names, primary key forced.
     *
     * Columns that do not exist on the table are silently dropped so callers
     * can opportunistically request fields like `image` without breaking the
     * query when a model hasn't added that column yet.
     */
    protected function applyFieldSelection(): static
    {
        $fields = $this->request->get('fields', []);
        if (empty($fields) || ! is_array($fields)) {
            return $this;
        }

        $instance = new $this->modelClass;
        $pk = $instance->getKeyName();
        $table = $instance->getTable();

        $existing = $instance->getConnection()
            ->getSchemaBuilder()
            ->getColumnListing($table);

        $safe = array_values(array_unique(array_merge(
            [$pk],
            array_filter(
                $fields,
                fn ($f) => is_string($f)
                    && preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $f)
                    && in_array($f, $existing, true),
            )
        )));

        $this->query->select($safe);

        return $this;
    }

    /**
     * ?sort=name&order=asc — sort column sanitized.
     */
    protected function applySorting(): static
    {
        $sort = $this->request->get('sort', 'id');
        $order = $this->request->get('order', 'asc') === 'desc' ? 'desc' : 'asc';

        if (is_string($sort) && preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $sort)) {
            $this->query->orderBy($sort, $order);
        } else {
            $this->query->orderBy('id', 'asc');
        }

        return $this;
    }
}
