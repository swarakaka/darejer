# Icon

> Renders a Lucide icon. Useful for decorative inline icons inside a screen body. For icons on buttons / links / actions, use the action's `->icon()` instead.

## Import / usage

```php
use Darejer\Components\Icon;

Icon::make('warning')->icon('AlertTriangle')->color('amber')
```

## Props

Inherits the shared API from `BaseComponent`.

| Method | Type | Default | Description |
|---|---|---|---|
| `icon($name)` | `string` | `'Circle'` | Lucide icon name (PascalCase) — see [lucide.dev](https://lucide.dev/icons). |
| `size($size)` | `string` | `'md'` | One of `xs \| sm \| md \| lg \| xl`. |
| `color($color)` | `string` | `null` | Tailwind color (`'red'`, `'amber'`, `'green'`, …) or hex. |
| `title($text)` | `string` | `null` | Accessibility title (becomes `<title>` inside the SVG). |

## Slots

None.

## Events

None.

## Examples

### Basic

```php
Icon::make('check')->icon('CheckCircle')->color('green')
```

### Intermediate

```php
Icon::make('shield')
    ->icon('ShieldCheck')
    ->size('lg')
    ->color('emerald')
    ->title('Verified account')
```

### Real-world — alongside text

```php
Screen::make('Settings')
    ->components([
        Icon::make('lock')
            ->icon('Lock')
            ->size('md')
            ->color('slate')
            ->title('Read-only setting'),
        TextInput::make('account_id')->readonly(),
    ])
    ->render();
```

## Accessibility

- When `title()` is set, the SVG carries a `<title>` element — screen readers announce it.
- Without `title()`, the icon is treated as decorative (`aria-hidden="true"`).

## Related

- Action icons — pass `->icon('Name')` on any [Action](../actions/README.md) instead of nesting an `Icon` component.
