# DropdownAction

> Wraps a list of actions in a dropdown menu. Used in screen toolbars when the action count exceeds what fits inline.

## Import / usage

```php
use Darejer\Actions\DropdownAction;
use Darejer\Actions\ButtonAction;
use Darejer\Actions\LinkAction;

DropdownAction::make('More')
    ->items([
        ButtonAction::make('Duplicate')->url(route('products.duplicate', $product))->method('POST')->icon('Copy'),
        LinkAction::make('Audit log')->url(route('products.audit', $product))->icon('History'),
        ButtonAction::make('Export PDF')->url(route('products.export', $product))->icon('Download'),
    ])
```

## Defaults

| Field | Default |
|---|---|
| `label` | `Actions` |
| `variant` | `outline` |
| `icon` | `ChevronDown` |

## Props

Inherits the [shared action API](README.md#the-shared-api), plus:

| Method | Type | Description |
|---|---|---|
| `items($actions)` | `BaseAction[]` | The actions rendered inside the dropdown. Each item respects its own `canSee()` + `dependOn()`. |

## Examples

### Basic

```php
DropdownAction::make('Actions')->items([
    ButtonAction::make('Refresh')->url(route('reports.refresh'))->method('POST'),
    LinkAction::make('Settings')->url(route('reports.settings')),
])
```

### Intermediate — mixed variants

```php
DropdownAction::make('More')
    ->items([
        LinkAction::make('Audit log')->url(route('products.audit', $product))->icon('History'),
        ButtonAction::make('Export')->url(route('products.export', $product))->icon('Download'),
        DeleteAction::make('Permanently delete')
            ->url(route('products.force-destroy', $product))
            ->canSee('products.force-delete'),
    ])
```

### Real-world — toolbar overflow

```php
Screen::make('Order')
    ->actions([
        SaveAction::make()->url(route('orders.update', $order))->method('PUT'),
        ButtonAction::make('Send')->url(route('orders.send', $order))->method('POST')->icon('Send'),

        DropdownAction::make('More')->items([
            LinkAction::make('Audit log')->url(route('orders.audit', $order)),
            ButtonAction::make('Duplicate')->url(route('orders.duplicate', $order))->method('POST'),
            ButtonAction::make('Export PDF')->url(route('orders.export', $order)),
            DeleteAction::make()->url(route('orders.destroy', $order)),
        ]),
    ])
    ->render();
```

## Accessibility

- Renders shadcn-vue `<DropdownMenu>` — `Enter` opens, `↑/↓` navigates items, `Esc` closes.

## Related

- [`ButtonAction`](button.md), [`LinkAction`](link.md) — typical items.
