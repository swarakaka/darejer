# PermissionGuard

> Wraps a group of components behind a permission check. When the user lacks the permission, **none** of the wrapped components are sent to the frontend.
>
> For per-component visibility, use `->canSee()` directly on the component. Use `PermissionGuard` to gate **groups** of fields cleanly.

## Import / usage

```php
use Darejer\Components\PermissionGuard;
use Darejer\Components\TextInput;
use Darejer\Components\Toggle;

PermissionGuard::make('products.admin')->components([
    TextInput::make('cost_price')->number(),
    Toggle::make('is_internal'),
])
```

## API

Not extending `BaseComponent` — its surface is minimal:

| Method | Description |
|---|---|
| `make($permission\|Closure)` | Static factory. Pass a permission string OR a closure returning bool. |
| `components($components)` | The group of `Componentable` instances to gate. |

## How it serializes

`toArray()` returns one of:

- `null` — user is denied; the group is stripped entirely from props.
- `{ type: '__guard__', components: [...] }` — when allowed. `Screen::serializeComponents()` unwraps the `__guard__` marker, flattening children into the top-level component list.

You don't see `__guard__` on the frontend — it's a transport-only sentinel.

## Examples

### Basic

```php
Screen::make('Product')
    ->components([
        TextInput::make('name')->required(),
        TextInput::make('sku')->required(),

        PermissionGuard::make('products.see-cost')->components([
            TextInput::make('cost_price')->number(),
            TextInput::make('margin')->number(),
        ]),
    ])
    ->render();
```

### Intermediate — with a closure

```php
PermissionGuard::make(fn () => auth()->user()->isInternal())
    ->components([
        TextInput::make('internal_notes'),
        Toggle::make('flag_for_review'),
    ])
```

### Real-world — admin-only block

```php
Screen::make('User profile')
    ->record($user)
    ->components([
        TextInput::make('name'),
        TextInput::make('email')->email()->readonly(),

        PermissionGuard::make('users.manage')->components([
            Combobox::make('role_id')->model(\Spatie\Permission\Models\Role::class),
            Toggle::make('is_active')->onLabel('Active')->offLabel('Suspended'),
            DatePicker::make('last_login_at')->disabled(),
        ]),
    ])
    ->render();
```

## Accessibility

No frontend rendering of its own — children render normally when allowed.

## Related

- The shared `canSee()` method on every component / action — for per-field gating.
- [`permissions.md`](../permissions.md) — full permission strategy.
