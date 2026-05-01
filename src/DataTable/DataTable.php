<?php

namespace Darejer\DataTable;

use Carbon\CarbonImmutable;
use Darejer\DataGrid\Column;
use Darejer\DataGrid\Filter;
use Darejer\DataGrid\RowAction;
use Darejer\Screen\Contracts\Actionable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DataTable
{
    protected string $title;

    protected string $modelClass;

    protected array $columns = [];

    protected array $filters = [];

    protected array $rowActions = [];

    protected array $headerActions = [];

    protected array $bulkActions = [];

    protected array $breadcrumbs = [];

    protected array $with = [];

    protected int $perPage = 15;

    protected ?string $defaultSort = null;

    protected string $defaultOrder = 'asc';

    protected bool $selectable = true;

    protected string $rowActionsDisplay = 'inline';

    protected ?string $emptyMessage = null;

    protected array $extraProps = [];

    protected bool $reorderable = false;

    protected string $reorderField = 'sort_order';

    protected bool $numeric = false;

    /**
     * Field name used by the synthetic row-number column.
     *
     * Underscored prefix avoids collisions with real model attributes.
     */
    protected const NUMERIC_FIELD = '__row_number';

    protected function __construct(string $modelClass)
    {
        $this->modelClass = $modelClass;
        $this->title = class_basename($modelClass).'s';
    }

    public static function make(string $modelClass): static
    {
        return new static($modelClass);
    }

    public function title(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function breadcrumbs(array $breadcrumbs): static
    {
        $this->breadcrumbs = $breadcrumbs;

        return $this;
    }

    /** @param Column[] $columns */
    public function columns(array $columns): static
    {
        $this->columns = $columns;

        return $this;
    }

    /** @param Filter[] $filters */
    public function filters(array $filters): static
    {
        $this->filters = $filters;

        return $this;
    }

    /** @param RowAction[] $rowActions */
    public function rowActions(array $rowActions): static
    {
        $this->rowActions = $rowActions;

        return $this;
    }

    /** @param Actionable[] $actions */
    public function headerActions(array $actions): static
    {
        $this->headerActions = $actions;

        return $this;
    }

    /**
     * Actions rendered in the bulk-selection strip that appears above the
     * table when one or more rows are selected. Pass BulkAction instances
     * (they POST to `batchUrl` with `batchParam => ids` by default).
     *
     * @param  Actionable[]  $actions
     */
    public function bulkActions(array $actions): static
    {
        $this->bulkActions = $actions;

        return $this;
    }

    public function with(array $relations): static
    {
        $this->with = $relations;

        return $this;
    }

    public function perPage(int $perPage): static
    {
        $this->perPage = $perPage;

        return $this;
    }

    public function defaultSort(string $field, string $order = 'asc'): static
    {
        $this->defaultSort = $field;
        $this->defaultOrder = $order;

        return $this;
    }

    public function selectable(bool $selectable = true): static
    {
        $this->selectable = $selectable;

        return $this;
    }

    /**
     * Choose how row actions render in the last column.
     *
     * - 'inline'   (default) — each action renders as an icon button with a tooltip.
     * - 'dropdown'           — actions collapse into a `…` menu.
     */
    public function rowActionsDisplay(string $mode): static
    {
        $this->rowActionsDisplay = $mode === 'dropdown' ? 'dropdown' : 'inline';

        return $this;
    }

    public function emptyMessage(string $message): static
    {
        $this->emptyMessage = $message;

        return $this;
    }

    /**
     * Enable drag-and-drop row reordering. The frontend renders a drag
     * handle column and POSTs the new order to
     *   POST /darejer/data/{model}/reorder  { ids: [3, 1, 2] }
     * which writes 1, 2, 3 to `$field` on the matching rows.
     *
     * Default field is `sort_order`. Defaults the table sort to the same
     * field so the UI starts from the persisted order.
     */
    public function reorderable(string $field = 'sort_order'): static
    {
        $this->reorderable = true;
        $this->reorderField = $field;

        if ($this->defaultSort === null) {
            $this->defaultSort = $field;
            $this->defaultOrder = 'asc';
        }

        return $this;
    }

    public function withExtra(array $props): static
    {
        $this->extraProps = array_merge($this->extraProps, $props);

        return $this;
    }

    /**
     * Prepend a continuous row-number column (`#`) whose value is the
     * absolute position of the row across pages — i.e. row 16 on page 2
     * with perPage 15 still shows `16`, not `1`. Off by default.
     */
    public function numeric(bool $numeric = true): static
    {
        $this->numeric = $numeric;

        return $this;
    }

    /**
     * Handle the request and return an Inertia response.
     */
    public function render(Request $request): Response
    {
        $query = $this->buildQuery($request);
        $paginated = $query->paginate($this->perPage)->withQueryString();

        $renderColumns = $this->buildRenderColumns();
        $startIndex = $paginated->firstItem() ?? 1;

        // Pre-resolve which columns request server-side date formatting so we
        // don't reflect over the column list per row.
        $dateColumns = collect($this->columns)
            ->filter(fn (Column $c) => in_array($c->getDisplayType(), ['date', 'datetime'], true))
            ->mapWithKeys(fn (Column $c) => [$c->getField() => $c->getDateFormat() ?: 'Y-m-d'])
            ->all();

        $booleanColumns = collect($this->columns)
            ->filter(fn (Column $c) => $c->getDisplayType() === 'boolean')
            ->mapWithKeys(fn (Column $c) => [$c->getField() => [
                'true' => $c->getBooleanTrueLabel() ?? __('Yes'),
                'false' => $c->getBooleanFalseLabel() ?? __('No'),
            ]])
            ->all();

        $displayUsingColumns = collect($this->columns)
            ->mapWithKeys(function (Column $column): array {
                $callback = $column->getDisplayUsing();

                if ($callback === null) {
                    return [];
                }

                return [$column->getField() => $callback];
            })
            ->all();

        $formatColumns = collect($this->columns)
            ->mapWithKeys(function (Column $column): array {
                $callback = $column->getFormat();

                if ($callback === null) {
                    return [];
                }

                return [$column->getField() => $callback];
            })
            ->all();

        $numeric = $this->numeric;
        $data = collect($paginated->items())->values()->map(function ($item, $index) use ($dateColumns, $booleanColumns, $displayUsingColumns, $formatColumns, $numeric, $startIndex) {
            $arr = $item->toArray();

            if ($numeric) {
                $arr[self::NUMERIC_FIELD] = $startIndex + $index;
            }

            if (method_exists($item, 'getTranslatableAttributes')) {
                foreach ($item->getTranslatableAttributes() as $attr) {
                    $arr[$attr] = $item->getTranslation($attr, app()->getLocale(), false)
                               ?: $item->getTranslation($attr, 'en', false)
                               ?: '';
                }
            }

            foreach ($dateColumns as $field => $format) {
                $value = $arr[$field] ?? null;
                if ($value === null || $value === '') {
                    continue;
                }
                try {
                    $arr[$field] = CarbonImmutable::parse((string) $value)->format($format);
                } catch (\Throwable) {
                    // Leave the raw value if it isn't parseable — surfaces
                    // bad data instead of hiding it.
                }
            }

            foreach ($booleanColumns as $field => $labels) {
                if (! array_key_exists($field, $arr)) {
                    continue;
                }
                $arr[$field] = filter_var($arr[$field], FILTER_VALIDATE_BOOLEAN)
                    ? $labels['true']
                    : $labels['false'];
            }

            foreach ($displayUsingColumns as $field => $callback) {
                try {
                    $arr[$field] = $callback($item);
                } catch (\Throwable) {
                    // Keep the raw value when callback mapping fails.
                }
            }

            foreach ($formatColumns as $field => $callback) {
                try {
                    $arr[$field] = $callback($item->{$field} ?? null, $item);
                } catch (\Throwable) {
                    // Keep the raw value when callback mapping fails.
                }
            }

            return $arr;
        })->all();

        Inertia::share('breadcrumbs', $this->breadcrumbs);

        return Inertia::render('DataTable', array_merge([
            'title' => $this->title,
            'columns' => array_map(fn (Column $c) => $c->toArray(), $renderColumns),
            'filters' => array_map(fn (Filter $f) => $f->toArray(), $this->filters),
            'rowActions' => array_map(fn (RowAction $a) => $a->toArray(), $this->rowActions),
            'headerActions' => collect($this->headerActions)
                ->map(fn (Actionable $a) => $a->toArray())
                ->filter()
                ->values()
                ->all(),
            'bulkActions' => collect($this->bulkActions)
                ->map(fn (Actionable $a) => $a->toArray())
                ->filter()
                ->values()
                ->all(),
            'selectable' => $this->selectable,
            'rowActionsDisplay' => $this->rowActionsDisplay,
            'reorderable' => $this->reorderable,
            'reorderField' => $this->reorderable ? $this->reorderField : null,
            'reorderUrl' => $this->reorderable
                ? route('darejer.data.reorder', ['model' => strtolower(class_basename($this->modelClass))])
                : null,
            'modelSlug' => strtolower(class_basename($this->modelClass)),
            'emptyMessage' => $this->emptyMessage,
            'defaultSort' => $this->defaultSort,
            'defaultOrder' => $this->defaultOrder,
            'tableData' => [
                'data' => $data,
                'total' => $paginated->total(),
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'from' => $paginated->firstItem(),
                'to' => $paginated->lastItem(),
                'links' => $paginated->linkCollection()->toArray(),
            ],
            'activeFilters' => $request->only(
                array_map(fn (Filter $f) => $f->toArray()['field'], $this->filters)
            ),
            'sort' => $request->get('sort', $this->defaultSort ?? 'id'),
            'order' => $request->get('order', $this->defaultOrder),
        ], $this->extraProps));
    }

    /**
     * Build the column list sent to the frontend.
     *
     * Hides the `id` column when present (the value is still serialized in
     * each row so row actions / selection can read `row.id`) and, if
     * {@see self::numeric()} was enabled, prepends a synthetic `#` column
     * carrying the absolute row position across pages.
     *
     * @return Column[]
     */
    protected function buildRenderColumns(): array
    {
        $columns = $this->columns;

        foreach ($columns as $column) {
            if ($column->getField() === 'id') {
                $column->hidden(true);
            }
        }

        if ($this->numeric) {
            array_unshift($columns, Column::make(self::NUMERIC_FIELD)
                ->label('#')
                ->width('60px')
                ->alignRight());
        }

        return $columns;
    }

    protected function buildQuery(Request $request): Builder
    {
        $query = $this->modelClass::query();

        if ($this->with) {
            $query->with($this->with);
        }

        foreach ($this->filters as $filter) {
            $filterArr = $filter->toArray();
            $field = $filterArr['field'];
            $value = $request->get($field);

            if ($value === null || $value === '') {
                continue;
            }

            match ($filterArr['type']) {
                'text' => $query->where($field, 'like', "%{$value}%"),
                'select' => $query->where($field, $value),
                'boolean' => $query->where($field, (bool) $value),
                'date' => $query->whereDate($field, $value),
                'daterange' => $this->applyDateRange($query, $field, $value),
                default => $query->where($field, 'like', "%{$value}%"),
            };
        }

        $search = $request->get('search', '');
        if ($search) {
            $searchable = collect($this->columns)
                ->filter(fn (Column $c) => $c->toArray()['searchable'] ?? false)
                ->map(fn (Column $c) => $c->toArray()['field'])
                ->values()
                ->all();

            if ($searchable) {
                $query->where(function (Builder $q) use ($searchable, $search) {
                    foreach ($searchable as $field) {
                        $q->orWhere($field, 'like', "%{$search}%");
                    }
                });
            }
        }

        $sort = $request->get('sort', $this->defaultSort ?? 'id');
        $order = $request->get('order', $this->defaultOrder) === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sort, $order);

        return $query;
    }

    protected function applyDateRange(Builder $query, string $field, mixed $value): void
    {
        if (is_array($value)) {
            if (! empty($value['from'])) {
                $query->whereDate($field, '>=', $value['from']);
            }
            if (! empty($value['to'])) {
                $query->whereDate($field, '<=', $value['to']);
            }
        }
    }
}
