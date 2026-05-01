# Toggle

> Switch-style boolean input. Visually distinct from `CheckboxComponent` — use Toggle for "active / inactive"-style settings.

## Import / usage

```php
use Darejer\Components\Toggle;

Toggle::make('is_active')->label('Active')->default(true)
```

## Props

Inherits the shared API from `BaseComponent`.

| Method | Type | Default | Description |
|---|---|---|---|
| `onLabel($text)` | `string` | `null` | Optional label shown next to the switch when on. |
| `offLabel($text)` | `string` | `null` | Optional label shown when off. |
| `disabled($flag = true)` | `bool` | `false` | |

## Slots

None.

## Events

None.

## Examples

### Basic

```php
Toggle::make('is_active')
```

### Intermediate

```php
Toggle::make('notifications_enabled')
    ->label('Email notifications')
    ->onLabel('On')
    ->offLabel('Off')
    ->default(true)
```

### Real-world — feature flag UI

```php
Screen::make('Settings')
    ->record($settings)
    ->components([
        Toggle::make('two_factor_required')->label('Require 2FA for all users'),
        Toggle::make('audit_logs_enabled')
            ->label('Audit logging')
            ->dependOn('plan', 'enterprise'),
    ])
    ->render();
```

## Accessibility

- Renders shadcn-vue `<Switch>` — keyboard `Space` / `Enter` toggles, arrow keys move focus.
- Wrapped in `<FieldWrapper>` for label association.

## Related

- [`CheckboxComponent`](checkbox.md) — checkbox-style boolean.
