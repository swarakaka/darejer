# Permissions

Authorization in Darejer is powered by [Spatie Laravel Permission](https://github.com/spatie/laravel-permission). Zero config — install the package and you're ready.

## How `canSee` resolves

Every component, action, navigation item, and the `PermissionGuard` accept a permission string OR a closure:

```php
TextInput::make('cost_price')->canSee('products.see-cost')

DeleteAction::make()->canSee('products.delete')

PermissionGuard::make(fn () => auth()->user()->isAdmin())
    ->components([/* … */])
```

### Resolution order

`canSee()` returns `true` (allow) when:

1. The check is `null` — no gate set; always visible.
2. The check is a closure that returns truthy.
3. The user is authenticated and has the role `super-admin` (Spatie convention; bypasses every check).
4. `$user->can($permission)` returns true.
5. `Gate::allows($permission)` returns true.

When a component or action is denied, **its serialized form is dropped from the Inertia payload** — frontend code can't even see that it exists. This is stricter than client-side `v-if`.

## Super-admin

Anyone with the `super-admin` Spatie role implicitly passes every check. The package wires a `Gate::before` callback in `DarejerServiceProvider::boot()`:

```php
Gate::before(fn ($user, $ability) =>
    method_exists($user, 'hasRole') && $user->hasRole('super-admin') ? true : null
);
```

## Configuration

Set the guard in `config/darejer.php`:

```php
'permissions' => [
    'guard' => 'web',
],
```

Spatie itself is configured via its own `config/permission.php` — publish it with `php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"` if you need to customize.

## User model

Add the Spatie trait:

```php
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles;
}
```

Or use the Darejer convenience trait that adds a few extra helpers:

```php
use Darejer\Concerns\HasDarejerPermissions;

class User extends Authenticatable
{
    use HasDarejerPermissions;
}
```

## Built-in admin screens

The package ships admin screens for managing users, roles, and permissions:

| URL | Purpose |
|---|---|
| `/darejer/users` | User list / CRUD |
| `/darejer/roles` | Role list / CRUD |
| `/darejer/permissions` | Permission list / CRUD |

These are auto-discovered via the package's own `controllers` config entry — no extra setup.

## Patterns

### Per-field gating

```php
TextInput::make('cost_price')->canSee('products.see-cost')
```

### Group gating with `PermissionGuard`

```php
PermissionGuard::make('products.admin')->components([
    TextInput::make('internal_id'),
    Toggle::make('flagged_for_review'),
])
```

When the user is denied, **none** of the wrapped fields are sent to the frontend.

### Action gating

```php
DeleteAction::make()
    ->url(route('products.destroy', $product))
    ->canSee('products.delete')
```

### Navigation items

```php
use Darejer\Navigation\NavItem;

NavItem::make('Settings')
    ->icon('Settings')
    ->route('settings.index')
    ->canSee('settings.view')
```

### DataGrid row actions

```php
RowAction::delete(RoutePattern::row('orders.destroy'))->canSee('orders.delete')
```

## Closures

Closures run on every request — keep them cheap. They're useful for per-record checks:

```php
DeleteAction::make()
    ->url(route('orders.destroy', $order))
    ->canSee(fn () => auth()->user()->id === $order->created_by_id)
```

## Testing

```php
$this->actingAs($user)
    ->givePermissionTo('products.see-cost');
```

Or use a Spatie role:

```php
$user->assignRole('super-admin');
```

## Related

- [Spatie Laravel Permission docs](https://spatie.be/docs/laravel-permission)
- [`components/permission-guard.md`](components/permission-guard.md) — the group-level gating component.
- [`auth/fortify.md`](auth/fortify.md) — how users authenticate first.
