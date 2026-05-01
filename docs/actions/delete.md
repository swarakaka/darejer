# DeleteAction

> Pre-set destructive button. Defaults to `DELETE` verb, destructive variant, `Trash2` icon, and a confirm prompt. Override the prompt via `->confirm()`.

## Import / usage

```php
use Darejer\Actions\DeleteAction;

DeleteAction::make()
    ->url(route('products.destroy', $product))
```

## Defaults

| Field | Default |
|---|---|
| `label` | `Delete` |
| `variant` | `destructive` |
| `method` | `DELETE` |
| `icon` | `Trash2` |
| `confirm` | `'Are you sure you want to delete this? This action cannot be undone.'` |

## Props

Inherits the [shared action API](README.md#the-shared-api). No additional props.

## Examples

### Basic

```php
DeleteAction::make()->url(route('products.destroy', $product))
```

### Intermediate — custom prompt + permission gate

```php
DeleteAction::make('Discard draft')
    ->url(route('quotes.discard', $quote))
    ->confirm('Discard this draft? You can\'t undo this.')
    ->dependOn('status', 'draft')
    ->canSee('quotes.delete')
```

### Real-world — soft-delete vs hard-delete

```php
DeleteAction::make('Archive')
    ->url(route('customers.archive', $customer))
    ->method('POST')
    ->variant('outline')
    ->icon('Archive')
    ->confirm('Archive this customer? They\'ll stop appearing in lists but the record is preserved.')
```

## Related

- [`SaveAction`](save.md), [`CancelAction`](cancel.md) — usual screen-toolbar siblings.
- [`DataGrid` `RowAction::delete()`](../components/data-grid.md#rowaction-api) — for per-row deletes inside a table.
