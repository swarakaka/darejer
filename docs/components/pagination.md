# Pagination

> Standalone server-side pagination control. Use this when you have a custom list rendered manually — `DataGrid` has its own built-in pagination.

## Import / usage

```php
use Darejer\Components\Pagination;

Pagination::make('products_pager')->dataKey('products')
```

## Props

Inherits the shared API from `BaseComponent`.

| Method | Type | Default | Description |
|---|---|---|---|
| `dataKey($key)` | `string` | `'data'` | Inertia page-prop key the pagination should sync against. |

## Slots

None.

## Events

None.

## Examples

### Basic

```php
Pagination::make('pager')->dataKey('users')
```

### Real-world — custom list

```php
public function index()
{
    return Screen::make('Users')
        ->with(['users' => User::paginate(20)])
        ->components([
            Pagination::make('pager')->dataKey('users'),
        ])
        ->render();
}
```

## Accessibility

- Renders a `<nav aria-label="Pagination">` with previous/next buttons and numbered page links.
- Current page exposes `aria-current="page"`.

## Related

- [`DataGrid`](data-grid.md) — has built-in pagination (you don't add this manually).
