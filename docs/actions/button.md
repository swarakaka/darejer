# ButtonAction

> Generic button. Use when no other specific action (`Save`, `Delete`, `Cancel`, `Link`) fits.

## Import / usage

```php
use Darejer\Actions\ButtonAction;

ButtonAction::make('Recalculate')
    ->url(route('orders.recalculate', $order))
    ->method('POST')
    ->icon('RefreshCw')
```

## Defaults

| Field | Default |
|---|---|
| `variant` | `outline` |
| `method` | `GET` |
| `icon` | — |

## Props

Inherits the [shared action API](README.md#the-shared-api). No additional props.

## Examples

### Basic

```php
ButtonAction::make('Refresh')
    ->url(route('reports.refresh'))
    ->icon('RefreshCw')
```

### Intermediate — dialog mode

```php
ButtonAction::make('Quick edit')
    ->dialog()
    ->url(route('products.edit', $product))
    ->icon('Pencil')
```

### Real-world — confirm + permission gate

```php
ButtonAction::make('Send to approval')
    ->url(route('quotes.submit', $quote))
    ->method('POST')
    ->variant('default')
    ->icon('Send')
    ->confirm('This will lock the quote for editing. Continue?')
    ->dependOn('status', 'draft')
    ->canSee('quotes.submit')
```

## Related

- [`LinkAction`](link.md) — when there's no method or side effect.
- [`SaveAction`](save.md) — for the canonical save in a form.
- [`DropdownAction`](dropdown.md) — group multiple buttons.
