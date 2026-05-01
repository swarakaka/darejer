# TreeGrid

> Hierarchical data table. Streams a flat dataset from `/darejer/data/{model}?tree=1`; the controller nests rows by `parent_field`.

## Import / usage

```php
use Darejer\Components\TreeGrid;
use Darejer\TreeGrid\TreeColumn;
use Darejer\DataGrid\RowAction;
use Darejer\Routing\RoutePattern;

TreeGrid::make('chart_of_accounts')
    ->model(\App\Models\Account::class)
    ->columns([
        TreeColumn::make('code')->tree()->sortable(),
        TreeColumn::make('name'),
        TreeColumn::make('balance')->alignRight(),
    ])
    ->rowActions([
        RowAction::edit(RoutePattern::row('accounts.edit')),
    ])
```

## Props

Inherits the shared API from `BaseComponent`.

| Method | Type | Default | Description |
|---|---|---|---|
| `model($class)` | `class-string<Model>` | — | Auto-generates `dataUrl` with `?tree=1`. |
| `dataUrl($url)` | `string` | `null` | Override the data endpoint. |
| `columns($cols)` | [`TreeColumn`](#treecolumn-api)[] | `[]` | Column definitions. |
| `rowActions($actions)` | [`RowAction`](data-grid.md#rowaction-api)[] | `[]` | Per-row buttons. Same API as DataGrid. |
| `parentField($field)` | `string` | `'parent_id'` | Foreign key that points at the parent row. |
| `keyField($field)` | `string` | `'id'` | Primary key. |
| `expandAll($flag = true)` | `bool` | `false` | Start with every row expanded. |
| `emptyMessage($text)` | `string` | `null` | Override the empty-state message. |

The `childrenKey` defaults to `'children'` — the JSON shape sent to the frontend. There is no public setter for this currently.

## TreeColumn API — `Darejer\TreeGrid\TreeColumn`

Static factory `TreeColumn::make($field)`.

| Method | Description |
|---|---|
| `label($text)` | Override the auto-derived label. |
| `sortable()` | Allow sorting on this column. |
| `width($width)` | CSS width. |
| `alignCenter()` / `alignRight()` | Cell alignment. |
| `tree()` | **Required on exactly one column** — that column shows the expand/collapse arrow + indentation. |

## Slots

None.

## Events

None.

## Examples

### Basic — categories tree

```php
TreeGrid::make('categories')
    ->model(Category::class)
    ->columns([
        TreeColumn::make('name')->tree(),
        TreeColumn::make('product_count')->alignRight(),
    ])
```

### Intermediate — expanded by default

```php
TreeGrid::make('org_chart')
    ->model(Department::class)
    ->columns([
        TreeColumn::make('name')->tree(),
        TreeColumn::make('manager_name'),
        TreeColumn::make('headcount')->alignRight(),
    ])
    ->expandAll()
```

### Real-world — accounts with row actions

```php
Screen::make('Chart of Accounts')
    ->components([
        TreeGrid::make('accounts')
            ->model(Account::class)
            ->parentField('parent_id')
            ->columns([
                TreeColumn::make('code')->tree()->width('10rem'),
                TreeColumn::make('name'),
                TreeColumn::make('type'),
                TreeColumn::make('balance')->alignRight(),
            ])
            ->rowActions([
                RowAction::edit(RoutePattern::row('accounts.edit')),
                RowAction::delete(RoutePattern::row('accounts.destroy'))
                    ->canSee('accounts.delete'),
            ]),
    ])
    ->render();
```

## Accessibility

- Each row exposes an expand/collapse button with `aria-expanded`.
- Tree structure is conveyed via `aria-level` and `aria-posinset`.

## Related

- [`DataGrid`](data-grid.md) — flat tabular data.
- [`Repeater`](repeater.md) — for editable hierarchies in forms.
