# RadioGroup

> Visible one-of-many selector. Use when the option count is small (≤ 5) and you want all options in view.

## Import / usage

```php
use Darejer\Components\RadioGroup;

RadioGroup::make('billing_cycle')->options([
    'monthly' => 'Monthly',
    'yearly'  => 'Yearly',
])
```

## Props

Inherits the shared API from `BaseComponent`.

| Method | Type | Default | Description |
|---|---|---|---|
| `options($options)` | `array\|class-string<BackedEnum>` | `[]` | `[value => label]` array OR a backed-enum class. |
| `horizontal()` | — | layout `vertical` | Render options side-by-side. |
| `disabled($flag = true)` | `bool` | `false` | |

## Slots

None.

## Events

None.

## Examples

### Basic

```php
RadioGroup::make('size')->options([
    's' => 'Small', 'm' => 'Medium', 'l' => 'Large',
])
```

### Intermediate — horizontal with default

```php
RadioGroup::make('plan')
    ->options(PlanType::class)
    ->horizontal()
    ->default('basic')
```

### Real-world — conditional fields

```php
Screen::make('Address')
    ->components([
        RadioGroup::make('address_type')
            ->horizontal()
            ->options([
                'residential' => 'Residential',
                'business'    => 'Business',
            ])
            ->default('residential'),

        TextInput::make('company_name')
            ->dependOn('address_type', 'business'),
    ])
    ->render();
```

## Accessibility

- Renders shadcn-vue `<RadioGroup>` — keyboard `↑/↓` (or `←/→`) to move between options, `Space` to select.
- The group label comes from `label()`; each radio is associated via `<FieldWrapper>`.

## Related

- [`SelectComponent`](select.md) — when option count exceeds ~5.
- [`CheckboxComponent`](checkbox.md) — single boolean.
