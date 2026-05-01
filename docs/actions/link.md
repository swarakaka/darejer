# LinkAction

> Pure hyperlink. Renders as a button, navigates via Inertia by default. Mark `->external()` to open in a new tab without going through Inertia.

## Import / usage

```php
use Darejer\Actions\LinkAction;

LinkAction::make('New product')
    ->url(route('products.create'))
    ->icon('Plus')
```

## Defaults

| Field | Default |
|---|---|
| `variant` | `default` |
| `method` | `GET` |
| `icon` | — |

## Props

Inherits the [shared action API](README.md#the-shared-api), plus:

| Method | Type | Default | Description |
|---|---|---|---|
| `external($flag = true)` | `bool` | `false` | Open the URL in a new tab; bypasses Inertia. |

## Examples

### Basic

```php
LinkAction::make('Back to list')->url(route('products.index'))
```

### Intermediate — external

```php
LinkAction::make('Open in Stripe')
    ->url('https://dashboard.stripe.com/customers/'.$customer->stripe_id)
    ->external()
    ->icon('ExternalLink')
```

### Real-world — primary "create" button on an index

```php
Screen::make('Products')
    ->components([
        DataGrid::make('products')->model(Product::class)->columns([/* … */]),
    ])
    ->actions([
        LinkAction::make('New product')
            ->url(route('products.create'))
            ->icon('Plus')
            ->variant('default'),
    ])
    ->render();
```

## Related

- [`ButtonAction`](button.md) — for actions with side effects.
- [`ModalToggleAction`](modal-toggle.md) — open a URL in a modal.
