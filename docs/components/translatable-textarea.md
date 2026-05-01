# TranslatableTextarea

> Multi-line textarea bound to a Spatie Translatable attribute. Renders a per-language tab UI when `darejer.languages` has more than one entry.

## Import / usage

```php
use Darejer\Components\TranslatableTextarea;

TranslatableTextarea::make('description')->rows(6)
```

## Props

Inherits the shared API from `BaseComponent`.

| Method | Type | Default | Description |
|---|---|---|---|
| `placeholder($text)` | `string` | `''` | |
| `rows($n)` | `int` | `4` | |
| `readonly($flag = true)` | `bool` | `false` | |
| `disabled($flag = true)` | `bool` | `false` | |

The `translatable: true` flag is always serialized.

## Slots

None.

## Events

None.

## Examples

### Basic

```php
TranslatableTextarea::make('description')
```

### Intermediate

```php
TranslatableTextarea::make('terms')
    ->label('Terms & conditions')
    ->rows(12)
    ->placeholder('Per-locale terms shown in the customer portal.')
```

### Real-world — see [`TranslatableInput`](translatable-input.md#real-world--multi-language-product)

## Accessibility

- Tabs are keyboard-navigable: `←/→` to switch locales, `Enter` to focus the textarea body.
- Each tab indicates whether content has been entered for that locale.

## Related

- [`TranslatableInput`](translatable-input.md) — single-line variant.
- [`Textarea`](textarea.md) — single-locale.
- [`translations.md`](../translations.md) — full strategy.
