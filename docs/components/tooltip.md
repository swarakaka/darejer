# TooltipComponent

> Standalone tooltip. Use this for explanatory floating text. For field-level tooltips, prefer the `tooltip()` method on any component (which integrates with the `<FieldWrapper>` info icon).

## Import / usage

```php
use Darejer\Components\TooltipComponent;

TooltipComponent::make('tip')
    ->trigger('Read more')
    ->content('Detailed explanation appears on hover.')
```

## Props

Inherits the shared API from `BaseComponent`.

| Method | Type | Default | Description |
|---|---|---|---|
| `trigger($text)` | `string` | `''` | The visible label. |
| `icon($iconName)` | `string` | — | Use a Lucide icon as the trigger instead of text. Sets `triggerType = 'icon'`. |
| `content($text)` | `string` | `''` | Tooltip body. |
| `side($side)` | `string` | `'top'` | One of `top \| right \| bottom \| left`. |

## Slots

None.

## Events

None.

## Examples

### Basic — text trigger

```php
TooltipComponent::make('help')
    ->trigger('What is SKU?')
    ->content('A unique merchant code identifying this product.')
    ->side('right')
```

### Intermediate — icon trigger

```php
TooltipComponent::make('info')
    ->icon('Info')
    ->content('Tax is calculated based on the customer billing address.')
    ->side('top')
```

### Real-world — info icon on a section header

```php
Screen::make('Order')
    ->components([
        TextInput::make('reference')->required(),
        TooltipComponent::make('tax_info')
            ->icon('Info')
            ->content('Tax is recalculated when you change the customer.'),
        TextInput::make('tax_amount')->number()->readonly(),
    ])
    ->render();
```

## Accessibility

- Renders shadcn-vue `<Tooltip>` — keyboard-reachable: focus the trigger and the tooltip surfaces.
- The tooltip has `role="tooltip"` and is wired to the trigger via `aria-describedby`.

## Related

- The `tooltip()` method on `BaseComponent` (every input/select/etc.) — preferred for field tooltips.
