# Configuration

Darejer is configured through a single file: `config/darejer.php`. Publish it with:

```bash
php artisan vendor:publish --tag=darejer-config
```

## Full config

```php
return [
    'languages'           => ['en'],
    'default_language'    => 'en',

    'permissions' => [
        'guard' => 'web',
    ],

    'pagination' => [
        'per_page' => 15,
    ],

    'uploads' => [
        'disk' => 'public',
        'path' => 'uploads',
    ],

    'route_prefix' => 'darejer',

    'middleware'   => ['web', 'auth'],

    'home_route'   => '/darejer',

    'dashboard_controller' => [
        \Darejer\Http\Controllers\DashboardController::class, 'index',
    ],

    'models' => [],

    'controllers' => [
        app_path('Http/Controllers') => 'App\\Http\\Controllers',
    ],
];
```

## Keys

### `languages` (array)
List every locale the platform should support. Read by:

- `TranslatableInput` / `TranslatableTextarea` — show multi-language UI only when this array has more than one entry.
- The Inertia shared prop `languages` — available in every Vue page.

```php
'languages' => ['en', 'ar', 'de', 'ckb'],
```

### `default_language` (string)
Falls back here when no locale is active.

### `permissions.guard` (string)
Auth guard used when checking `$user->can(...)` on `canSee()` calls.

### `pagination.per_page` (int)
Default page size for `DataGrid`, `DataController`. Override per-component with `->perPage()`.

### `uploads.disk` / `uploads.path`
Default disk + folder used by `FileUpload` when not overridden via `->disk()` / `->path()`.

### `route_prefix` (string)
URL prefix mounted on every package route — including Fortify (login, logout, password reset, etc.). Default `darejer`. Setting this to `''` mounts at root.

> Fortify reads `darejer.route_prefix` via the Darejer-shipped `config/fortify.php`. If you publish the Fortify config and change its prefix, keep both in sync.

### `middleware` (array)
Middleware applied to authenticated package routes (`/`, `/data/*`, `/alerts/*`, `/search`).

### `home_route` (string)
Path users land on after login. Stored as a path — not a route name — so it can be used as the default for `redirect()->intended($home)`.

### `dashboard_controller` (callable)
Action invoked by `GET /{prefix}`. Override to ship your own KPI tiles, charts, lists. Must return `Inertia::render('Dashboard', [...])`.

```php
'dashboard_controller' => [\App\Http\Controllers\DashboardController::class, 'index'],
```

### `models` (array)
Map URL slugs to fully-qualified model class names. `App\Models\*` is resolved automatically — only register custom namespaces:

```php
'models' => [
    'order'   => \Domain\Sales\Models\Order::class,
    'invoice' => \Domain\Billing\Models\Invoice::class,
],
```

The slugs power `GET /darejer/data/{model}` (used by `Combobox`, `DataGrid`, `Kanban`, `TreeGrid`).

### `controllers` (array)
Map of absolute directory path → PSR-4 namespace. Darejer scans these for subclasses of `Darejer\Http\Controllers\DarejerController` and auto-registers their routes.

Set to `[]` to disable auto-routing globally:

```php
'controllers' => [],
```

Add custom namespaces for modular apps:

```php
'controllers' => [
    app_path('Http/Controllers')                   => 'App\\Http\\Controllers',
    base_path('domains/Sales/Http/Controllers')    => 'Domains\\Sales\\Http\\Controllers',
    base_path('domains/Billing/Http/Controllers')  => 'Domains\\Billing\\Http\\Controllers',
],
```

## Inertia shared props

The Darejer middleware (`Darejer\Http\Middleware\HandleInertiaRequests`) shares these on every page:

| Prop | Source | Notes |
|---|---|---|
| `languages` | `darejer.languages` | Used by translatable components |
| `default_language` | `darejer.default_language` | |
| `locale` | active locale | Resolved per-request |
| `direction` | `ltr` / `rtl` | Auto from locale |
| `auth.user` | logged-in user | |
| `flash.success` / `flash.error` | session flash | Picked up by `<FlashMessage>` |
| `breadcrumbs` | from `Screen::breadcrumbs()` | Read by `<AppBreadcrumbs>` |

## Console commands

| Command | What it does |
|---|---|
| `php artisan darejer:install` | Run interactive setup |
| `php artisan darejer:language` | Add/remove languages |
| `php artisan darejer:language:export` | Export translations to JSON |

## Next

- [Architecture: Controller + auto-routing](../architecture/controller.md)
- [Build your first screen](first-screen.md)
