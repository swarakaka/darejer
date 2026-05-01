# HTTP Rules

> **This rule is strictly enforced.** Code review rejects PRs that break it.

The frontend uses **only Inertia primitives** to talk to the backend — `useHttp`, `useForm`, and `router`. `fetch`, `axios`, and `XMLHttpRequest` are forbidden anywhere in `resources/js/`.

This page exists so backend developers can reason about how their PHP endpoints will be consumed.

---

## The matrix

| When you need to… | Use | Backend should return |
|---|---|---|
| Fetch JSON data (Combobox options, DataGrid pages, autocomplete) | `useHttp` from `@inertiajs/vue3` | JSON envelope (see [`json-envelope.md`](json-envelope.md)) |
| Submit a form (create / update) | `useForm` from `@inertiajs/vue3` | Inertia redirect with flash/errors, OR JSON 422 on validation failure |
| Navigate / sort / filter / paginate | `router` from `@inertiajs/vue3` | Inertia `Inertia::render(...)` response |
| PATCH a single field (Kanban drop, InPlaceEditor save) | `useHttp` (PATCH) | JSON success envelope |
| DELETE a single record (DataGrid row action) | `useHttp` (DELETE) | JSON success envelope or Inertia redirect |
| **Anything else** | _N/A_ | _Forbidden_ |

---

## What this means for backend code

### A controller method called from `useHttp` must return JSON

```php
public function update(Request $request, Product $product)
{
    if ($request->expectsJson()) {
        $product->update($request->validated());
        return $this->jsonSuccess('Updated', $product);
    }

    $product->update($request->validated());
    return redirect()->route('products.edit', $product)
        ->with('success', 'Updated');
}
```

The base `DarejerController` exposes four envelope helpers — `json`, `jsonSuccess`, `jsonError`, `jsonValidation`. Always use them so every endpoint speaks the same shape.

### Validation errors

Both `useHttp` and `useForm` populate the same Inertia error bag. From PHP:

```php
$validator = Validator::make($request->all(), $rules);
if ($validator->fails()) {
    return $this->jsonValidation($validator);
    // → 422 { "message": "…", "errors": { "field": ["…"] } }
}
```

For `useForm` consumers, `throw ValidationException` works equivalently — Inertia surfaces it the same way.

### Single-field PATCH (Kanban, InPlaceEditor)

The Kanban component drag-drops PATCH a record's status field. The endpoint is the package's `darejer.data.update`:

```
PATCH /darejer/data/{model}/{id}
{ "field": "status", "value": "in_progress" }
```

You don't write this endpoint — Darejer ships it. But the model's `$fillable` must include the field for the update to succeed. See [`components/kanban.md`](../components/kanban.md).

---

## The serialization contract

`Screen::render()` serializes components into JSON page props. The frontend's `<Screen>` component reads them and never makes its own decisions about what to fetch. This means:

- A component that needs backend data ships a `dataUrl` in its props (see `Combobox`, `DataGrid`, `Kanban`, `TreeGrid`, `Scheduler`, `Gantt`, `Diagram`).
- The frontend calls that URL via `useHttp` — never via `axios.get` or `fetch`.
- Your controller serves the URL and returns the JSON envelope.

```php
// PHP
Components\Combobox::make('category_id')->model(Category::class)
// → ships dataUrl: "/darejer/data/category"

// Frontend reads dataUrl and calls useHttp() with it
// → which hits your auto-registered DataController::index
// → which returns the JSON envelope
```

---

## When you might be tempted to break the rule

> "I just want a quick `axios.post` to ping an endpoint."

Use `useHttp({ method: 'POST', url: '...' })` instead. It's the same primitive, and it preserves the Inertia version header so middleware doesn't 409-bounce you.

> "But this is a third-party API call from the frontend."

Then proxy it through a Laravel controller. Frontend code never talks to non-Darejer hosts directly.

> "I need a CSRF-free endpoint."

Move it out of the `web` middleware group, but still consume it via `useHttp`. The rule is about Inertia primitives, not about CSRF.

---

## Related

- [`architecture/json-envelope.md`](json-envelope.md) — the JSON shape every endpoint returns.
- [`architecture/controller.md`](controller.md) — how controllers register their routes.
- [`architecture/screen-engine.md`](screen-engine.md) — how Screens reach the frontend.
