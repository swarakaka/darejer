<?php

namespace Darejer\DataTable;

use BackedEnum;
use Carbon\CarbonImmutable;
use Darejer\DataGrid\Column;
use Darejer\DataGrid\Filter;
use Darejer\DataGrid\RowAction;
use Darejer\Screen\Contracts\Actionable;
use Darejer\Support\EnumOptions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
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
     * Dot-notated path whose per-row value selects a row text-color variant.
     */
    protected ?string $rowColorField = null;

    /** @var array<string, string>|null */
    protected ?array $rowColorMap = null;

    /**
     * Dot-notated path whose per-row value selects a row background variant.
     */
    protected ?string $rowBgField = null;

    /** @var array<string, string>|null */
    protected ?array $rowBgMap = null;

    /**
     * Field name used by the synthetic row-number column.
     *
     * Underscored prefix avoids collisions with real model attributes.
     */
    protected const NUMERIC_FIELD = '__row_number';

    /**
     * Field carrying the resolved per-row color variant for the frontend.
     *
     * Underscored prefix avoids collisions with real model attributes.
     */
    protected const ROW_VARIANT_FIELD = '__row_variant';

    /**
     * Field carrying the resolved per-row background variant for the frontend.
     *
     * Underscored prefix avoids collisions with real model attributes.
     */
    protected const ROW_BG_VARIANT_FIELD = '__row_bg_variant';

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
     * Color every cell in a row based on a field's value — the row-level
     * analogue of `Column::textColorBy()`.
     *
     * `$field` is a dot-notated path on the record whose value selects the
     * variant from `$colorMap`. Accepts either a `[value => variant]` array or
     * a backed-enum class string that exposes per-case colors via `color()`
     * methods or a static `colors()` helper — same resolution as `badge()`.
     *
     * Variants follow the `success`/`warning`/`danger`/`info`/`neutral`/
     * `muted`/`ink` palette. Unmapped values leave the row at its default ink.
     *
     * @param  array<string, string>|class-string<BackedEnum>  $colorMap
     */
    public function rowColorBy(string $field, array|string $colorMap): static
    {
        $this->rowColorField = $field;
        $this->rowColorMap = EnumOptions::colors($colorMap);

        return $this;
    }

    /**
     * Tint a whole row's background based on a field's value — the background
     * companion to `rowColorBy()`. Both may be combined on the same table.
     *
     * `$field` is a dot-notated path on the record whose value selects the
     * variant from `$colorMap` (a `[value => variant]` array or a backed-enum
     * class string). Variants follow the `success`/`warning`/`danger`/`info`/
     * `neutral`/`muted` palette and render as a subtle tinted background.
     * Unmapped values leave the row at its default background.
     *
     * @param  array<string, string>|class-string<BackedEnum>  $colorMap
     */
    public function rowBackgroundBy(string $field, array|string $colorMap): static
    {
        $this->rowBgField = $field;
        $this->rowBgMap = EnumOptions::colors($colorMap);

        return $this;
    }

    /**
     * Handle the request and return an Inertia response.
     */
    public function render(Request $request): Response
    {
        $query = $this->buildQuery($request);
        $footerValues = $this->buildFooterValues($query);
        $paginated = $query->paginate($this->perPage)->withQueryString();

        $renderColumns = $this->buildRenderColumns();
        $startIndex = $paginated->firstItem() ?? 1;

        // Pre-resolve which columns request server-side date formatting so we
        // don't reflect over the column list per row.
        $dateColumns = collect($this->columns)
            ->filter(fn (Column $c) => in_array($c->getDisplayType(), ['date', 'datetime'], true))
            ->mapWithKeys(fn (Column $c) => [$c->getField() => $c->getDateFormat() ?: 'Y/m/d'])
            ->all();

        $booleanColumns = collect($this->columns)
            ->filter(fn (Column $c) => $c->getDisplayType() === 'boolean')
            ->mapWithKeys(fn (Column $c) => [$c->getField() => [
                'true' => $c->getBooleanTrueLabel() ?? __('Yes'),
                'false' => $c->getBooleanFalseLabel() ?? __('No'),
            ]])
            ->all();

        $numberColumns = collect($this->columns)
            ->filter(fn (Column $c) => $c->getDisplayType() === 'number')
            ->mapWithKeys(fn (Column $c) => [$c->getField() => $c->getDecimals()])
            ->all();

        $absoluteColumns = collect($this->columns)
            ->filter(fn (Column $c) => $c->isAbsolute())
            ->mapWithKeys(fn (Column $c) => [$c->getField() => true])
            ->all();

        $signColumns = collect($this->columns)
            ->filter(fn (Column $c) => $c->getSignColorMap() !== null)
            ->mapWithKeys(fn (Column $c) => [$c->getField() => $c->getSignField()])
            ->all();

        $moneyColumns = collect($this->columns)
            ->filter(fn (Column $c) => $c->getDisplayType() === 'money')
            ->mapWithKeys(fn (Column $c) => [$c->getField() => [
                'decimals' => $c->getDecimals(),
                'currencyField' => $c->getCurrencyField(),
                'decimalsField' => $c->getDecimalsField(),
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

        $badgeEnumColumns = collect($this->columns)
            ->mapWithKeys(function (Column $column): array {
                $enum = $column->getBadgeEnum();

                return $enum ? [$column->getField() => $enum] : [];
            })
            ->all();

        $numeric = $this->numeric;
        $rowColorField = $this->rowColorField;
        $rowColorMap = $this->rowColorMap ?? [];
        $rowBgField = $this->rowBgField;
        $rowBgMap = $this->rowBgMap ?? [];
        $data = collect($paginated->items())->values()->map(function ($item, $index) use ($dateColumns, $booleanColumns, $numberColumns, $moneyColumns, $absoluteColumns, $signColumns, $displayUsingColumns, $formatColumns, $badgeEnumColumns, $numeric, $rowColorField, $rowColorMap, $rowBgField, $rowBgMap, $startIndex) {
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

            foreach ($numberColumns as $field => $decimals) {
                if (! array_key_exists($field, $arr)) {
                    continue;
                }
                $value = self::coerceNumeric($arr[$field]);
                if ($value === null) {
                    continue;
                }
                if (isset($absoluteColumns[$field])) {
                    $value = abs($value);
                }
                $arr[$field] = number_format($value, $decimals, '.', ',');
            }

            foreach ($moneyColumns as $field => $config) {
                if (! array_key_exists($field, $arr)) {
                    continue;
                }
                $value = self::coerceNumeric($arr[$field]);
                if ($value === null) {
                    continue;
                }
                if (isset($absoluteColumns[$field])) {
                    $value = abs($value);
                }
                $decimals = $config['decimals'];
                if ($config['decimalsField']) {
                    $resolved = data_get($item, $config['decimalsField']);
                    if (is_numeric($resolved)) {
                        $decimals = (int) $resolved;
                    }
                }
                $formatted = number_format($value, $decimals, '.', ',');
                if ($config['currencyField']) {
                    $code = data_get($item, $config['currencyField']);
                    if ($code !== null && $code !== '') {
                        $formatted .= ' '.$code;
                    }
                }
                $arr[$field] = $formatted;
            }

            foreach ($badgeEnumColumns as $field => $enumClass) {
                if (! array_key_exists($field, $arr)) {
                    continue;
                }
                $case = self::resolveEnumCase($enumClass, $arr[$field]);
                if ($case === null) {
                    continue;
                }
                $arr[$field] = [
                    'label' => method_exists($case, 'label') ? $case->label() : (string) $case->value,
                    'variant' => method_exists($case, 'color') ? $case->color() : 'neutral',
                ];
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

            foreach ($signColumns as $field => $signField) {
                $raw = self::coerceNumeric(data_get($item, $field));
                $arr[$signField] = match (true) {
                    $raw === null => 'zero',
                    $raw < 0 => 'negative',
                    $raw > 0 => 'positive',
                    default => 'zero',
                };
            }

            if ($rowColorField !== null) {
                $variant = $rowColorMap[self::scalarKey(data_get($item, $rowColorField))] ?? null;
                if ($variant !== null) {
                    $arr[self::ROW_VARIANT_FIELD] = $variant;
                }
            }

            if ($rowBgField !== null) {
                $variant = $rowBgMap[self::scalarKey(data_get($item, $rowBgField))] ?? null;
                if ($variant !== null) {
                    $arr[self::ROW_BG_VARIANT_FIELD] = $variant;
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
            'footerValues' => $footerValues,
        ], $this->extraProps));
    }

    /**
     * Run aggregate queries for each footer-bearing column across the entire
     * filtered dataset (not just the visible page) and format the results.
     *
     * @return array<string, string>
     */
    protected function buildFooterValues(Builder $query): array
    {
        $footerColumns = array_values(array_filter(
            $this->columns,
            fn (Column $c) => $c->getFooter() !== null,
        ));

        if ($footerColumns === []) {
            return [];
        }

        $base = $query->clone()->reorder();
        $values = [];

        foreach ($footerColumns as $col) {
            $field = $col->getField();
            $cloned = $base->clone();

            if ($callback = $col->getFooterCallback()) {
                $raw = $callback($cloned);
            } else {
                $raw = match ($col->getFooter()) {
                    'sum' => $cloned->sum($field),
                    'avg' => $cloned->avg($field),
                    'min' => $cloned->min($field),
                    'max' => $cloned->max($field),
                    'count' => $cloned->count(),
                    default => null,
                };
            }

            $values[$field] = $this->formatFooterValue($col, $raw);
        }

        return $values;
    }

    protected function formatFooterValue(Column $col, mixed $raw): string
    {
        if ($raw === null) {
            return '';
        }

        $type = $col->getDisplayType();
        $value = self::coerceNumeric($raw);

        if ($col->getFooter() === 'count') {
            return number_format((float) $raw, 0, '.', ',');
        }

        if ($value === null) {
            return (string) $raw;
        }

        if ($type === 'money' || $type === 'number') {
            return number_format($value, $col->getDecimals(), '.', ',');
        }

        return number_format($value, 0, '.', ',');
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
            $type = $filterArr['type'];
            $value = $request->get($field);

            if ($type === 'trashed') {
                $this->applyTrashedScope($query, $value);

                continue;
            }

            if ($value === null || $value === '') {
                continue;
            }

            match ($type) {
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

    /**
     * Coerce a serialized cell value to a float for `number_format`.
     *
     * Eloquent's `toArray()` leaves custom cast values (e.g. `Brick\Math\BigDecimal`)
     * as objects in the array; `is_numeric()` rejects objects, so we must
     * stringify them first. Returns null for non-numeric/empty values.
     */
    /**
     * Normalise a row value into a string map key, unwrapping backed enums
     * (e.g. an enum-cast `direction` column) to their scalar value so it
     * matches the keys produced by EnumOptions::colors().
     */
    protected static function scalarKey(mixed $value): string
    {
        if ($value instanceof BackedEnum) {
            return (string) $value->value;
        }

        return (string) $value;
    }

    protected static function coerceNumeric(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_object($value) && method_exists($value, '__toString')) {
            $value = (string) $value;
        }
        if (! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    /**
     * Resolve a backed-enum case from a row value, tolerating both string and
     * integer enum backings and PHP booleans (which Eloquent's boolean cast
     * surfaces). Booleans try `'true'/'false'` first (matches the YesNo
     * convention) then `'1'/'0'` for integer-backed enums.
     *
     * @param  class-string<BackedEnum>  $enumClass
     */
    protected static function resolveEnumCase(string $enumClass, mixed $value): ?BackedEnum
    {
        if (! is_subclass_of($enumClass, BackedEnum::class)) {
            return null;
        }
        if ($value instanceof BackedEnum) {
            return $value instanceof $enumClass ? $value : null;
        }
        if ($value === null) {
            return null;
        }

        $candidates = is_bool($value)
            ? [$value ? 'true' : 'false', $value ? 1 : 0, $value ? '1' : '0']
            : [$value, is_numeric($value) ? (int) $value : null, (string) $value];

        foreach ($candidates as $candidate) {
            if ($candidate === null || (! is_int($candidate) && ! is_string($candidate))) {
                continue;
            }
            $case = $enumClass::tryFrom($candidate);
            if ($case !== null) {
                return $case;
            }
        }

        return null;
    }

    /**
     * Apply the soft-delete scope chosen by the trashed filter.
     *
     * Silently no-ops on models that don't use the SoftDeletes trait so the
     * filter can live in shared filter sets without per-model gating.
     */
    protected function applyTrashedScope(Builder $query, mixed $value): void
    {
        $uses = class_uses_recursive($this->modelClass);
        if (! in_array(SoftDeletes::class, $uses, true)) {
            return;
        }

        match ($value) {
            'with' => $query->withTrashed(),
            'only' => $query->onlyTrashed(),
            default => null, // empty / unknown → default scope (withoutTrashed)
        };
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
