# PHP API Reference

The complete public PHP surface of the Darejer package. Anything not listed here is internal — don't depend on it.

## Contents

- [`Darejer\Http\Controllers\DarejerController`](#darejerhttpcontrollersdarejercontroller)
- [`Darejer\Routing\Route` (attribute)](#darejerroutingroute)
- [`Darejer\Routing\RoutePattern`](#darejerroutingroutepattern)
- [`Darejer\Screen\Screen`](#darejerscreenscreen)
- [`Darejer\Screen\Section`](#darejerscreensection)
- [`Darejer\Screen\Tab`](#darejerscreentab)
- [`Darejer\Forms\Form`](#darejerformsform)
- [`Darejer\Components\BaseComponent`](#darejercomponentsbasecomponent)
- [`Darejer\Actions\BaseAction`](#darejeractionsbaseaction)
- [`Darejer\Components\PermissionGuard`](#darejercomponentspermissionguard)
- [`Darejer\Navigation\NavigationManager`](#sidebar-navigation--navigationmanager)
- [`Darejer\DataGrid\Column` / `Filter` / `RowAction`](#darejerdatagrid-supporting-classes)
- [`Darejer\TreeGrid\TreeColumn`](#darejertreegridtreecolumn)
- [`Darejer\EditableTable\Column`](#darejereditabletablecolumn)
- [`Darejer\Kanban\KanbanColumn` / `KanbanCard`](#darejerkanban)
- [`Darejer\Concerns\*`](#darejerconcerns)
- [`Darejer\Support\EnumOptions`](#darejersupportenumoptions)
- [Helpers](#helpers)
- [Console commands](#console-commands)
- [Service provider](#service-provider)

---

## `Darejer\Http\Controllers\DarejerController`

Abstract base controller. Subclasses get auto-routing, JSON envelope helpers, and form publishing. See [`architecture/controller.md`](../architecture/controller.md).

### Properties

| Property | Type | Default | Description |
|---|---|---|---|
| `$resource` | `?string` | `null` | URI segment + route-name prefix. |
| `$routeName` | `?string` | `$resource` | Override for the route-name prefix. |
| `$model` | `?string` | `null` | Eloquent model FQCN for implicit binding. |
| `$routeMiddleware` | `array` | `['web', 'auth']` | Middleware on auto-registered routes. |
| `$routePrefix` | `?string` | `null` | Optional URL prefix on top of `$resource`. |
| `$parameter` | `?string` | `Str::singular($resource)` | Route param name. |
| `$autoRoute` | `bool` | `true` | Set `false` to opt out of discovery. |

### Methods

| Method | Returns | Description |
|---|---|---|
| `darejerAutoRoute()` | `bool` | Read-only accessor for `$autoRoute`. |
| `darejerResource()` | `?string` | Read-only accessor for `$resource`. |
| `darejerRouteName()` | `?string` | `$routeName ?? $resource`. |
| `darejerModel()` | `?string` | Read-only accessor for `$model`. |
| `darejerMiddleware()` | `array` | Read-only accessor for `$routeMiddleware`. |
| `darejerRoutePrefix()` | `?string` | Read-only accessor for `$routePrefix`. |
| `darejerParameter()` | `string` | Resolves `$parameter ?? Str::singular($resource ?? 'record')`. |
| `forms()` | `array` | Override to expose reusable `Form` definitions at `GET /{resource}/forms/{name}`. |
| `json($data, $status, $meta)` | `JsonResponse` | Standard data envelope. |
| `jsonSuccess($message, $data, $status)` | `JsonResponse` | Action acknowledgement. |
| `jsonError($message, $status, $errors)` | `JsonResponse` | Generic error. |
| `jsonValidation($errors, $message)` | `JsonResponse` | 422 validation envelope. |

---

## `Darejer\Routing\Route`

Method attribute for declaring custom non-REST routes on a controller. `IS_REPEATABLE`.

```php
#[Route('POST', 'bulk/archive', name: 'bulk.archive', middleware: ['can:products.archive'])]
public function bulkArchive(Request $request) { … }
```

| Param | Type | Default | Description |
|---|---|---|---|
| `method` | `string \| string[]` | — | HTTP verb(s). |
| `uri` | `string` | — | Path appended under the resource slug. |
| `name` | `?string` | `null` | Route name suffix (prefixed with controller's route name). |
| `middleware` | `array` | `[]` | Extra middleware. |
| `absolute` | `bool` | `false` | If true, `uri` is registered verbatim (no resource prefix); the `name` is also not prefixed. |

---

## `Darejer\Routing\RoutePattern`

Helper for generating URL templates with `{id}` placeholders for `RowAction` / dialog URL patterns.

| Method | Returns | Description |
|---|---|---|
| `RoutePattern::row($routeName, $placeholder = '{id}')` | `string` | Resolve a registered route and substitute its first parameter with `{id}` (or another placeholder). |
| `RoutePattern::for($routeName, $placeholders)` | `string` | Multi-parameter variant; pass `[paramName => placeholder]`. |

```php
RoutePattern::row('products.edit')             // → "/products/{id}/edit"
RoutePattern::for('invoices.lines.edit', [
    'invoice' => '{invoiceId}',
    'line'    => '{lineId}',
])                                              // → "/invoices/{invoiceId}/lines/{lineId}/edit"
```

---

## `Darejer\Screen\Screen`

The screen builder. See [`architecture/screen-engine.md`](../architecture/screen-engine.md).

| Method | Returns | Description |
|---|---|---|
| `Screen::make($title)` | `static` | Factory. |
| `title($title)` | `static` | |
| `record($model\|$array\|null)` | `static` | Bind the record. |
| `breadcrumbs($crumbs)` | `static` | Topbar breadcrumb trail. |
| `components($components)` | `static` | `Componentable[]`. |
| `actions($actions)` | `static` | `Actionable[]`. |
| `sections($sections)` | `static` | `Section[]`. |
| `tabs($tabs)` | `static` | `Tab[]`. |
| `dialog($size = 'md')` | `static` | Render as modal. Sizes: `xs\|sm\|md\|lg\|xl\|full`. |
| `fullWidth($flag = true)` | `static` | |
| `with($props)` | `static` | Merge extra Inertia props. |
| `toArray()` | `array` | Inert payload (mostly for tests). |
| `render()` | `Inertia\Response` | Serialize and render. |

---

## `Darejer\Screen\Section`

| Method | Returns |
|---|---|
| `Section::make($title)` | `static` |
| `title($title)` | `static` |
| `components($names)` | `static` |
| `collapsed($flag = true)` | `static` |
| `alwaysExpanded($flag = true)` | `static` |
| `dependOn($rule)` | `static` |
| `toArray()` | `array` |

## `Darejer\Screen\Tab`

| Method | Returns |
|---|---|
| `Tab::make($title)` | `static` |
| `title($title)` | `static` |
| `components($names)` | `static` |
| `dependOn($rule)` | `static` |
| `toArray()` | `array` |

---

## `Darejer\Forms\Form`

Reusable form definition. Used as a Screen-builder twin and as the source of `GET /{resource}/forms/{name}`.

| Method | Returns | Description |
|---|---|---|
| `Form::make($name)` | `static` | |
| `getName()` | `string` | |
| `title($title)` | `static` | |
| `components($components)` | `static` | |
| `record($model\|$array\|null)` | `static` | |
| `save($url, $method = 'POST', $label = null)` | `static` | Add a Save action. |
| `cancel($url = null, $label = null)` | `static` | Add a Cancel action. |
| `cancelLabel($label)` | `static` | Override label only. Pass `null` to suppress the cancel button. |
| `addActions($actions)` | `static` | Append on top of Save/Cancel. |
| `actions($actions)` | `static` | Replace the entire toolbar. |
| `tabs($tabs)` | `static` | |
| `sections($sections)` | `static` | |
| `breadcrumbs($crumbs)` | `static` | |
| `with($props)` | `static` | |
| `fullWidth($flag = true)` | `static` | |
| `toArray()` | `array` | JSON shape for `useHttp` consumers. |
| `renderAsScreen()` | `Inertia\Response` | Full Inertia page (the standard `create()` / `edit()` experience). |

---

## `Darejer\Components\BaseComponent`

Abstract base for every component. Concrete components extend this and implement `componentType()` and `componentProps()`. The shared API:

| Method | Returns |
|---|---|
| `static make($name)` | `static` |
| `label($label)` | `static` |
| `required($flag = true)` | `static` |
| `hint($hint)` | `static` |
| `tooltip($text)` | `static` |
| `default($value)` | `static` |
| `fullWidth($flag = true)` | `static` |
| `dependOn($field, $value)` | `static` |
| `dependOnNot($field, $value)` | `static` |
| `dependOnNotEmpty($field)` | `static` |
| `dependOnEmpty($field)` | `static` |
| `dependOnIn($field, $values)` | `static` |
| `dependOnNotIn($field, $values)` | `static` |
| `dependOnConditions($conds, $logic = 'and')` | `static` |
| `canSee($permission\|Closure)` | `static` |
| `toArray()` | `?array` (returns `null` when hidden) |

Concrete components: see [`components/README.md`](../components/README.md).

---

## `Darejer\Actions\BaseAction`

Abstract base for every action. Concrete actions extend this and implement `actionType()` and optionally `actionProps()`. The shared API:

| Method | Returns |
|---|---|
| `static make($label)` | `static` |
| `label($text)` | `static` |
| `url($url)` | `static` |
| `method($verb)` | `static` |
| `dialog()` | `static` |
| `canSee($permission\|Closure)` | `static` |
| `icon($name)` | `static` |
| `confirm($message)` | `static` |
| `variant($variant)` | `static` |
| `disabled($flag = true)` | `static` |
| `tooltip($text)` | `static` |
| `fullWidth()` | `static` |
| `dependOn($field, $value, $operator = 'eq')` | `static` |
| `dependOnNotEmpty($field)` | `static` |
| `dependOnIn($field, $values)` | `static` |
| `dependOnConditions($conds, $logic = 'and')` | `static` |
| `toArray()` | `?array` (returns `null` when hidden) |

Concrete actions: see [`actions/README.md`](../actions/README.md).

---

## `Darejer\Components\PermissionGuard`

| Method | Returns |
|---|---|
| `static make($permission\|Closure)` | `static` |
| `components($components)` | `static` |
| `toArray()` | `?array` |

See [`components/permission-guard.md`](../components/permission-guard.md).

---

## Sidebar navigation — `NavigationManager`

The package's sidebar navigation is driven by `Darejer\Navigation\NavigationManager`. Host apps register `NavItem` instances in their service provider:

```php
use Darejer\Navigation\NavigationManager;
use Darejer\Navigation\NavItem;

class AppServiceProvider extends ServiceProvider
{
    public function boot(NavigationManager $nav): void
    {
        $nav->add([
            NavItem::make('Sales')
                ->icon('ShoppingCart')
                ->children([
                    NavItem::make('Orders')->route('orders.index'),
                    NavItem::make('Quotations')->route('quotations.index')->canSee('quotations.view'),
                ]),

            NavItem::make('Inventory')
                ->icon('Package')
                ->route('products.index')
                ->badge('New', 'success'),
        ]);
    }
}
```

### `Darejer\Navigation\NavItem`

| Method | Returns |
|---|---|
| `static make($label)` | `static` |
| `icon($icon)` | `static` |
| `url($url)` | `static` |
| `route($name, ...$params)` | `static` (URL resolved at render time) |
| `children($children)` | `static` |
| `group($label)` | `static` |
| `canSee($permission\|Closure)` | `static` |
| `badge($text, $color = 'info')` | `static` |
| `isVisible()` | `bool` |
| `toArray()` | `array` |

---

## `Darejer\DataGrid\*` supporting classes

### `Column`

See [`components/data-grid.md`](../components/data-grid.md#column-api) for the full API.

### `Filter`

See [`components/filter-panel.md`](../components/filter-panel.md#filter-api) for the full API.

### `RowAction`

See [`components/data-grid.md`](../components/data-grid.md#rowaction-api) for the full API.

---

## `Darejer\TreeGrid\TreeColumn`

See [`components/tree-grid.md`](../components/tree-grid.md#treecolumn-api).

## `Darejer\EditableTable\Column`

See [`components/editable-table.md`](../components/editable-table.md#column-api).

## `Darejer\Kanban`

- `KanbanColumn` — see [`components/kanban.md`](../components/kanban.md#kanbancolumn-api).
- `KanbanCard` — see [`components/kanban.md`](../components/kanban.md#kanbancard-api).

---

## `Darejer\Concerns\*`

Eloquent traits for models.

| Trait | Purpose |
|---|---|
| `HasDarejerPermissions` | Spatie `HasRoles` + a few extra helpers used by the admin screens. |
| `HasDarejerTranslatable` | Pair with Spatie's `HasTranslations` for ergonomic helpers. |
| `Auditable` | Append-only audit log for model changes. Writes via `Darejer\Support\AuditWriter`. |
| `Searchable` | Marks a model for inclusion in the package's global search. The trait expects a `searchable()` method on the model returning a `[field => weight]` map. |

---

## `Darejer\Support\EnumOptions`

Helpers for backed-enum interop. Used internally by `SelectComponent`, `RadioGroup`, `Combobox`, `Filter`, `Display`, `DataGrid\Column`.

| Method | Returns | Description |
|---|---|---|
| `EnumOptions::labels($enumClassOrArray)` | `array<string, string>` | `[value => label]`. Resolves via `label()` instance method, static `options()`, or `name` fallback. |
| `EnumOptions::colors($enumClassOrArray)` | `array<string, string>` | `[value => color]`. Resolves via `color()` instance method or static `colors()`. |

---

## Helpers

Globally available functions (autoloaded via `composer.json` `files`).

| Function | Description |
|---|---|
| `__darejer($key, $replace = [], $locale = null)` | Lookup `darejer::darejer.{key}`. See [`translations.md`](../translations.md). |

---

## Console commands

| Signature | Class | Description |
|---|---|---|
| `darejer:install` | `Darejer\Console\Commands\InstallCommand` | Interactive setup. |
| `darejer:language` | `Darejer\Console\Commands\LanguageCommand` | Add/remove a language. |
| `darejer:language:export` | `Darejer\Console\Commands\LanguageExportCommand` | Export translations to JSON. |

---

## Service provider

`Darejer\DarejerServiceProvider` is auto-discovered. It:

1. Merges `config/darejer.php`.
2. Applies Darejer-friendly Fortify defaults (unless `config/fortify.php` is published).
3. Registers `Gate::before(...)` for super-admin bypass.
4. Publishes config / migrations / Fortify config / compiled assets.
5. Auto-loads package migrations.
6. Loads package translations from `lang/`.
7. Registers Spatie's Role/Permission models in the `ModelRegistry`.
8. Discovers + registers controllers via `ControllerRouteRegistrar`.
9. Registers the `@darejerAssets` Blade directive.
10. Appends `HandleInertiaRequests` to the `web` middleware group.

`Darejer\Providers\FortifyServiceProvider` wires Fortify GET views to Inertia pages — see [`auth/fortify.md`](../auth/fortify.md).

---

## Internal — do not depend on

These exist in the codebase but are **not** part of the public API. They may change without notice:

- `Darejer\Routing\ControllerRouteRegistrar`
- `Darejer\Data\DataQuery`, `DataTransformer`, `ModelRegistry`
- `Darejer\Http\Controllers\DataController` (use through `dataUrl` props)
- `Darejer\Http\Controllers\Admin\*`
- `Darejer\Http\Middleware\HandleInertiaRequests`
- `Darejer\Helpers\AssetHelper`
- `Darejer\Support\AuditWriter`, `GlobalSearch`, `Locales`, `Alert`
- `Darejer\Models\Alert`
- Anything under `resources/js/` — the entire frontend is internal.
