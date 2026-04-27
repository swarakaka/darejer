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
        $labelField = $this->request->get('label', 'name');

        if ($search !== '' && $search !== null) {
            // Only allow a-z/_ column names to avoid SQL injection via label.
            if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $labelField)) {
                $this->query->where($labelField, 'like', "%{$search}%");
            }
        }

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
