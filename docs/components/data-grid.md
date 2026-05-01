# DataGrid

> Server-side paginated table with sortable / searchable columns, row actions, optional row selection, and per-row filters.
>
> Data is streamed from a `dataUrl` (auto-derived from `->model()`) — never inlined into props.

## Import / usage

```php
use Darejer\Components\DataGrid;
use Darejer\DataGrid\Column;
use Darejer\DataGrid\RowAction;
use Darejer\Routing\RoutePattern;

DataGrid::make('products')
    ->model(\App\Models\Product::class)
    ->columns([
        Column::make('sku')->sortable(),
        Column::make('name')->sortable()->searchable(),
        Column::make('price')->alignRight(),
    ])
    ->rowActions([
        RowAction::edit(RoutePattern::row('products.edit')),
        RowAction::delete(RoutePattern::row('products.destroy')),
    ])
```

## Props

Inherits the shared API from `BaseComponent`.

| Method | Type | Default | Description |
|---|---|---|---|
| `model($class)` | `class-string<Model>` | — | Bind to a model — auto-generates `dataUrl` to `/darejer/data/{slug}`. |
| `dataUrl($url)` | `string` | `null` | Override the data endpoint. |
| `columns($cols)` | [`Column`](#column-api)[] | `[]` | Column definitions. |
| `rowActions($actions)` | [`RowAction`](#rowaction-api)[] | `[]` | Per-row action buttons. |
| `perPage($n)` | `int` | `15` | Page size sent to the server. |
| `selectable($flag = true)` | `bool` | `false` | Render row checkboxes. |
| `emptyMessage($text)` | `string` | `null` | Override the empty-state message. |
| `defaultSort($field, $order = 'asc')` | `string`, `string` | `null`, `'asc'` | Initial sort. |
| `searchable($flag = true)` | `bool` | `true` | Show the table-level search input. |

## Slots

None.

## Events

None on the backend — the grid manages its own state via `useHttp`. Row actions trigger their own URLs.

## Column API — `Darejer\DataGrid\Column`

Fluent column definition. Static factory `Column::make($field)`.

| Method | Description |
|---|---|
| `label($text)` | Override the auto-derived "Sentence Case" label. |
| `sortable($flag = true)` | Allow sorting on this column. |
| `searchable($flag = true)` | Include this column in the table-level search query. |
| `width($width)` | CSS width — e.g. `'8rem'`, `'160px'`, `'20%'`. |
| `hidden($flag = true)` | Hide by default; user can toggle if column-toggle UI is shown. |
| `alignCenter()` / `alignRight()` | Cell alignment. |
| `format(Closure)` | Server-side `fn ($value, $row) => …` value transformer. |
| `displayUsing(callable)` | Same as `format` but with a callable signature. |
| `badge($colorMap, $labels = null)` | Render value as a colored badge. Accepts `[value => 'success\|warning\|danger\|info\|neutral']` or a backed-enum class. Optional label override. |
| `date($format = 'Y-m-d')` | Format the value as a date server-side. |
| `dateTime($format = 'Y-m-d H:i')` | Format as datetime. |
| `boolean($trueLabel = null, $falseLabel = null)` | Render as Yes/No (translated). |
| `display($type, $format = null)` | Override the auto-resolved display type — e.g. `'plain'` to opt out. |

## RowAction API — `Darejer\DataGrid\RowAction`

| Method | Description |
|---|---|
| `make($label)` | Generic factory. |
| `edit($urlPattern)` | Pre-set "Edit" with `Pencil` icon. |
| `view($urlPattern)` | Pre-set "View" with `Eye` icon. |
| `delete($urlPattern)` | Pre-set "Delete" with confirm prompt + `Trash2` icon + destructive variant. |
| `icon($name)` | Lucide icon name. |
| `type($type)` | `'link' \| 'delete'` — controls method/UX. |
| `url($pattern)` | URL pattern; the frontend substitutes `{id}` per row. Use [`RoutePattern::row(...)`](../api-reference/php-api.md#darejerroutingroutepattern). |
| `dialog($flag = true)` | Open the URL in a dialog. |
| `confirm($message)` | Show a confirmation prompt before activating. |
| `variant($variant)` | `'default' \| 'destructive' \| 'outline' \| 'ghost' \| 'secondary' \| 'link'`. |
| `canSee($permission\|Closure)` | Hide from users who lack the permission. |

## Examples

### Basic

```php
DataGrid::make('users')
    ->model(\App\Models\User::class)
    ->columns([
        Column::make('name')->sortable()->searchable(),
        Column::make('email')->searchable(),
    ])
```

### Intermediate — formatted columns + filters

```php
DataGrid::make('orders')
    ->model(\App\Models\Order::class)
    ->defaultSort('created_at', 'desc')
    ->columns([
        Column::make('reference')->sortable(),
        Column::make('customer_name')->searchable(),
        Column::make('total')->money(2)->alignRight(),
        Column::make('status')->badge([
            'pending'   => 'warning',
            'paid'      => 'success',
            'cancelled' => 'danger',
        ]),
        Column::make('placed_at')->dateTime(),
    ])
    ->rowActions([
        RowAction::view(RoutePattern::row('orders.show')),
        RowAction::edit(RoutePattern::row('orders.edit')),
        RowAction::delete(RoutePattern::row('orders.destroy'))
            ->canSee('orders.delete'),
    ])
```

### Real-world — selectable rows + bulk action

```php
Screen::make('Products')
    ->components([
        DataGrid::make('products')
            ->model(Product::class)
            ->selectable()
            ->columns([
                Column::make('sku')->sortable(),
                Column::make('name')->sortable()->searchable(),
                Column::make('is_active')->boolean(),
            ])
            ->rowActions([
                RowAction::edit(RoutePattern::row('products.edit')),
            ]),
    ])
    ->actions([
        Actions\BulkAction::make('Archive selected')
            ->batchUrl(route('products.bulk.archive'))
            ->icon('Archive')
            ->confirm('Archive selected products?'),
    ])
    ->render();
```

## Accessibility

- Headers are `<th scope="col">` with sort buttons exposing `aria-sort`.
- Row checkboxes (when `selectable`) have a parent "select all" with indeterminate state.
- Search and pagination are keyboard-reachable; pagination follows the `<nav aria-label="…pagination">` pattern.

## Related

- [`FilterPanel`](filter-panel.md) — pair with DataGrid for advanced filtering.
- [`TreeGrid`](tree-grid.md) — hierarchical data.
- [`Pagination`](pagination.md) — for non-DataGrid lists.
- [`api-reference/php-api.md`](../api-reference/php-api.md#darejerroutingroutepattern) — `RoutePattern` for row-action URLs.
