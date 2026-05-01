# CancelAction

> Cancel / back button. Navigates away without saving. Defaults to outline variant + `X` icon.

## Import / usage

```php
use Darejer\Actions\CancelAction;

CancelAction::make()->url(route('products.index'))
```

## Defaults

| Field | Default |
|---|---|
| `label` | `Cancel` |
| `variant` | `outline` |
| `method` | `GET` |
| `icon` | `X` |

## Props

Inherits the [shared action API](README.md#the-shared-api). No additional props.

## Examples

### Basic

```php
CancelAction::make()->url(route('products.index'))
```

### Intermediate — translated label

```php
CancelAction::make(__('Go back'))->url(url()->previous())
```

### Real-world — paired with Save and Delete

```php
Screen::make('Edit Product')
    ->record($product)
    ->components([/* … */])
    ->actions([
        SaveAction::make()->url(route('products.update', $product))->method('PUT'),
        DeleteAction::make()->url(route('products.destroy', $product))->canSee('products.delete'),
        CancelAction::make()->url(route('products.index')),
    ])
    ->render();
```

## Related

- [`SaveAction`](save.md), [`DeleteAction`](delete.md) — usual neighbors.
