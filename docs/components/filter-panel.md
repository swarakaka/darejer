# FilterPanel

> Bar or sidebar of filters that controls another data component (typically a `DataGrid`). Filters propagate to the controlled component's `dataUrl` query string.

## Import / usage

```php
use Darejer\Components\FilterPanel;
use Darejer\DataGrid\Filter;

FilterPanel::make('product_filters')
    ->controls('products')
    ->filters([
        Filter::text('name'),
        Filter::select('category_id')->options($categoryOptions),
        Filter::boolean('is_active'),
        Filter::dateRange('created_at')->label('Created'),
    ])
```

## Props

Inherits the shared API from `BaseComponent`.

| Method | Type | Default | Description |
|---|---|---|---|
| `filters($filters)` | [`Filter`](#filter-api)[] | `[]` | Filter definitions. |
| `controls($componentName)` | `string` | `''` | The `name` of the component this panel filters. Must match the `name()` you gave to e.g. a `DataGrid`. |
| `bar()` | — | layout `bar` | Render as a horizontal bar (default). |
| `sidebar()` | — | — | Render as a vertical sidebar. |
| `collapsed($flag = true)` | `bool` | `false` | Start collapsed (sidebar mode only). |

## Filter API — `Darejer\DataGrid\Filter`

Static factory `Filter::make($field)` plus typed shortcuts.

| Method | Description |
|---|---|
| `make($field)` | Generic; type defaults to `'text'`. |
| `text($field)` | Text input filter. |
| `select($field)` | Select dropdown. |
| `date($field)` | Single-date picker. |
| `dateRange($field)` | Range picker. |
| `boolean($field)` | Yes / No / All. |
| `label($text)` | Override the auto-derived label. |
| `type($type)` | Override the type. |
| `options($options)` | `[value => label]` array OR a backed-enum class — used by select/boolean filters. |
| `placeholder($text)` | |
| `default($value)` | Initial filter value. |

## Slots

None.

## Events

None on the backend — filters become query-string parameters on the controlled component's `dataUrl`.

## Examples

### Basic — bar mode

```php
Screen::make('Products')
    ->components([
        FilterPanel::make('product_filters')
            ->controls('products')
            ->filters([
                Filter::text('name'),
                Filter::select('category_id')->options($categoryOptions),
            ]),

        DataGrid::make('products')
            ->model(Product::class)
            ->columns([/* … */]),
    ])
    ->render();
```

### Intermediate — sidebar with defaults

```php
FilterPanel::make('order_filters')
    ->controls('orders')
    ->sidebar()
    ->collapsed()
    ->filters([
        Filter::select('status')
            ->options(OrderStatus::class)
            ->default('pending'),
        Filter::dateRange('placed_at')->label('Placed between'),
        Filter::boolean('is_paid')->default(false),
    ])
```

### Real-world — enum-driven

```php
FilterPanel::make('audit_filters')
    ->controls('audit_log')
    ->bar()
    ->filters([
        Filter::select('event')->options(AuditEvent::class),
        Filter::select('user_id')->options($activeUsers),
        Filter::dateRange('created_at')->label('Date range'),
    ])
```

## Accessibility

- Filter inputs are wrapped in `<FieldWrapper>` for label association.
- Apply / clear buttons are keyboard-reachable.
- Sidebar collapsed state is announced via `aria-expanded` on the toggle.

## Related

- [`DataGrid`](data-grid.md) — typical target.
- [`TreeGrid`](tree-grid.md) — also filterable.
