# SaveAction

> The canonical "Save" button on create/edit screens. Pre-wired with `POST` + `default` variant + `Save` icon.

## Import / usage

```php
use Darejer\Actions\SaveAction;

SaveAction::make()
    ->url(route('products.store'))
    ->method('POST')
```

## Defaults

| Field | Default |
|---|---|
| `label` | `Save` |
| `variant` | `default` |
| `method` | `POST` |
| `icon` | `Save` |

## Props

Inherits the [shared action API](README.md#the-shared-api). No additional props.

## Examples

### Basic — create

```php
SaveAction::make()->url(route('products.store'))->method('POST')
```

### Intermediate — update

```php
SaveAction::make('Update')
    ->url(route('products.update', $product))
    ->method('PUT')
```

### Real-world — translated label, custom icon

```php
SaveAction::make(__('Submit for approval'))
    ->url(route('quotes.submit', $quote))
    ->method('POST')
    ->icon('Send')
    ->canSee('quotes.submit')
```

## Behaviour

The frontend collects the surrounding `<Screen>` form state and submits it via `useForm` to the action's URL. Validation errors come back via Inertia's standard error bag — every component's `<FieldWrapper>` picks up its own messages.

## Related

- [`CancelAction`](cancel.md) — its usual neighbor.
- [`ButtonAction`](button.md) — when the save flow is non-standard.
- [`architecture/json-envelope.md`](../architecture/json-envelope.md) — response shape on error.
