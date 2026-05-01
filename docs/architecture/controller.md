# Controller & Auto-Routing

Every host-app controller in a Darejer project extends `Darejer\Http\Controllers\DarejerController`. This base class provides:

1. **Auto-routing** — declare `$resource` and standard REST endpoints register themselves.
2. **Custom endpoint attribute** — `#[Darejer\Routing\Route]` for non-REST routes.
3. **Implicit model binding** — set `$model` and the route parameter binds to it.
4. **JSON envelope helpers** — `json()`, `jsonSuccess()`, `jsonError()`, `jsonValidation()`.
5. **Form publishing** — return reusable `Form` instances from `forms()` and they're auto-exposed at `GET /{resource}/forms/{name}`.

> **Never** write CRUD routes for these controllers in `routes/web.php`. They duplicate the auto-registered ones.

---

## Minimal example

```php
namespace App\Http\Controllers;

use App\Models\Product;
use Darejer\Http\Controllers\DarejerController;

class ProductController extends DarejerController
{
    protected ?string $resource = 'products';
    protected ?string $model    = Product::class;

    public function index()  { /* GET    /products              → products.index   */ }
    public function create() { /* GET    /products/create       → products.create  */ }
    public function store()  { /* POST   /products              → products.store   */ }
    public function show(Product $product)   { /* GET    /products/{product}        → products.show    */ }
    public function edit(Product $product)   { /* GET    /products/{product}/edit   → products.edit    */ }
    public function update(Product $product) { /* PUT    /products/{product}        → products.update  */ }
    public function destroy(Product $product){ /* DELETE /products/{product}        → products.destroy */ }
}
```

---

## Configurable properties

| Property | Default | Description |
|---|---|---|
| `$resource` | `null` | URI segment + route-name prefix. When `null`, only `#[Route]` attributes register. |
| `$routeName` | `$resource` | Override the route-name prefix when it should differ from the URI. |
| `$model` | `null` | Eloquent model FQCN — wires implicit binding for `show`/`edit`/`update`/`destroy`. |
| `$routeMiddleware` | `['web', 'auth']` | Middleware applied to every auto-registered route. |
| `$routePrefix` | `null` | Optional URL prefix stacked on top of `$resource` (e.g. `'admin'`). |
| `$parameter` | `Str::singular($resource)` | Route parameter name. `products` → `{product}`. |
| `$autoRoute` | `true` | Set `false` to suppress discovery for this controller. |

---

## Auto-routed REST verbs

The registrar matches on **method presence** — only methods you actually define get registered:

| Method | URI | Route name |
|---|---|---|
| `index` | `GET    {resource}` | `{name}.index` |
| `create` | `GET    {resource}/create` | `{name}.create` |
| `store` | `POST   {resource}` | `{name}.store` |
| `show` | `GET    {resource}/{param}` | `{name}.show` |
| `edit` | `GET    {resource}/{param}/edit` | `{name}.edit` |
| `update` | `PUT|PATCH {resource}/{param}` | `{name}.update` |
| `destroy` | `DELETE {resource}/{param}` | `{name}.destroy` |

The `{param}` is constrained to `[0-9]+`. Word-shaped sibling URIs like `/products/kanban` fall through to attribute routes instead of being captured as the model id.

---

## Custom endpoints — `#[Route]` attribute

Use the attribute for any non-REST endpoint. The `name` is appended to the controller's route-name prefix; the `uri` is appended to the resource slug.

```php
use Darejer\Routing\Route;

class ProductController extends DarejerController
{
    protected ?string $resource = 'products';

    #[Route('POST', 'bulk/archive', name: 'bulk.archive')]
    public function bulkArchive(Request $request)
    {
        // → POST /products/bulk/archive
        // → name products.bulk.archive
        return $this->jsonSuccess('Archived', ['count' => $count]);
    }

    #[Route('GET', 'kanban', name: 'kanban')]
    public function kanban()
    {
        // → GET /products/kanban
        // → name products.kanban
        return Screen::make('Pipeline')->components([/* … */])->render();
    }

    // Multiple verbs:
    #[Route(['POST', 'PUT'], 'restore/{id}', name: 'restore', middleware: ['can:products.restore'])]
    public function restore(int $id) { /* … */ }

    // Absolute (don't append to resource):
    #[Route('GET', '/health', name: 'health', absolute: true)]
    public function health() { return $this->jsonSuccess('ok'); }
}
```

### Attribute parameters

| Parameter | Type | Default | Description |
|---|---|---|---|
| `method` | `string` or `string[]` | — | HTTP verb(s). |
| `uri` | `string` | — | Path, appended under the resource slug unless `absolute: true`. |
| `name` | `string\|null` | `null` | Route name suffix (prefixed with controller's route name). |
| `middleware` | `string[]` | `[]` | Extra middleware. Merged with controller-level middleware. |
| `absolute` | `bool` | `false` | When `true`, `uri` is registered verbatim (no resource prefix). |

The attribute is `IS_REPEATABLE` — declare multiple `#[Route(...)]` lines on the same method to expose it under several verbs/URIs.

---

## Disabling auto-routing

Per controller:

```php
class ProductController extends DarejerController
{
    protected bool $autoRoute = false;
}
```

Globally — set `darejer.controllers` to `[]` in `config/darejer.php`.

---

## Forms — `forms()` method

Override `forms()` to expose reusable `Form` definitions. Each form is auto-exposed at `GET /{resource}/forms/{name}` so frontend consumers (e.g. the `Combobox` inline-create dialog) can fetch the form schema via `useHttp` without an Inertia navigation.

```php
use Darejer\Forms\Form;

class ProductController extends DarejerController
{
    protected ?string $resource = 'products';

    public function forms(): array
    {
        return [
            Form::make('quick-create')
                ->components([
                    Components\TextInput::make('name')->required(),
                    Components\TextInput::make('sku')->required(),
                ]),
        ];
    }
}
```

→ Auto-registered: `GET /products/forms/quick-create` → name `products.forms`.

---

## Response helpers

All four return Laravel `JsonResponse` with the [standard envelope](json-envelope.md).

| Helper | Purpose |
|---|---|
| `$this->json($data, $status = 200, $meta = [])` | Wrap a model / collection / paginator. |
| `$this->jsonSuccess($message, $data = null)` | Action acknowledgment (bulk action, status toggle). |
| `$this->jsonError($message, $status, $errors = [])` | Generic error. |
| `$this->jsonValidation($validator)` | 422 from a Validator instance or `[field => [messages]]` array. |

---

## How discovery works

`DarejerServiceProvider::boot()` calls:

```php
ControllerRouteRegistrar::discover([
    app_path('Http/Controllers') => 'App\\Http\\Controllers',
])->register();
```

The registrar:
1. Walks each directory with Symfony Finder.
2. Reflects every PHP class whose FQCN matches the PSR-4 mapping.
3. Skips abstract classes and any class not extending `DarejerController`.
4. For each match, registers REST routes (when those methods exist), the `forms` route (when `forms()` is overridden), and any `#[Route]` attribute methods.
5. Honours Laravel's route cache — when routes are cached, discovery is skipped.

Customize the directory map via `darejer.controllers` in [config](../getting-started/configuration.md).

---

## Related

- [`architecture/screen-engine.md`](screen-engine.md) — what controllers return.
- [`architecture/http-rules.md`](http-rules.md) — the strict frontend HTTP API.
- [`architecture/json-envelope.md`](json-envelope.md) — the JSON shape returned by helpers.
- [`api-reference/php-api.md`](../api-reference/php-api.md) — full method catalog.
