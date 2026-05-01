# ModalToggleAction

> Opens a URL as a modal overlay. Defaults to `dialog: true` so the frontend renders the target inside a shadcn-vue `<Dialog>` instead of navigating.

## Import / usage

```php
use Darejer\Actions\ModalToggleAction;

ModalToggleAction::make('Quick view')
    ->url(route('products.show', $product))
    ->size('lg')
```

## Defaults

| Field | Default |
|---|---|
| `variant` | `outline` |
| `method` | `GET` |
| `dialog` | `true` |
| `icon` | — |

## Props

Inherits the [shared action API](README.md#the-shared-api), plus:

| Method | Type | Default | Description |
|---|---|---|---|
| `size($size)` | `string` | `'md'` | Modal size: `xs \| sm \| md \| lg \| xl \| full`. |

## Examples

### Basic

```php
ModalToggleAction::make('Edit')->url(route('products.edit', $product))
```

### Intermediate — extra-large preview

```php
ModalToggleAction::make('Preview')
    ->url(route('products.preview', $product))
    ->size('xl')
    ->icon('Eye')
```

### Real-world — paired with a `Screen::make()->dialog()` controller

```php
// Screen action:
ModalToggleAction::make('Quick edit')
    ->url(route('products.edit', $product))
    ->size('lg')

// Controller:
public function edit(Product $product)
{
    return Screen::make('Edit Product')
        ->dialog('lg')                         // ← match the size
        ->record($product)
        ->components([/* … */])
        ->actions([/* … */])
        ->render();
}
```

## Related

- [`Screen::dialog()`](../architecture/screen-engine.md#dialog-mode) — the rendering side.
- [`LinkAction::dialog()`](../actions/link.md) / [`ButtonAction::dialog()`](../actions/button.md) — alternatives without the modal-specific helpers.
