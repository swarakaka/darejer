# CheckboxComponent

> Single boolean checkbox. Use `Toggle` for an on/off switch UI; use `RadioGroup` or `SelectComponent` for multi-option choices.

## Import / usage

```php
use Darejer\Components\CheckboxComponent;

CheckboxComponent::make('terms_accepted')
    ->checkboxLabel('I agree to the terms')
    ->required()
```

## Props

Inherits the shared API from `BaseComponent`.

| Method | Type | Default | Description |
|---|---|---|---|
| `checkboxLabel($text)` | `string` | `null` | Inline label rendered next to the checkbox. The standard `label()` is the field-level label above. |
| `disabled($flag = true)` | `bool` | `false` | |

## Slots

None.

## Events

None.

## Examples

### Basic

```php
CheckboxComponent::make('subscribe')->checkboxLabel('Send me product updates')
```

### Intermediate

```php
CheckboxComponent::make('is_default')
    ->label('Address')
    ->checkboxLabel('Use as default shipping address')
    ->default(false)
    ->dependOnNotEmpty('city')
```

### Real-world — product flags

```php
Screen::make('Product')
    ->components([
        TextInput::make('name')->required(),
        CheckboxComponent::make('is_featured')->checkboxLabel('Feature on homepage'),
        CheckboxComponent::make('is_taxable')->checkboxLabel('Apply tax')->default(true),
    ])
    ->render();
```

## Accessibility

- Renders shadcn-vue `<Checkbox>` — keyboard `Space` toggles state.
- Both `label()` and `checkboxLabel()` are wired to the input via `<FieldWrapper>`.

## Related

- [`Toggle`](toggle.md) — switch-style boolean.
- [`RadioGroup`](radio-group.md) — for one-of-many.
- [`SelectComponent`](select.md) — same, dropdown.
