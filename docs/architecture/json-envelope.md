# JSON Envelope

Darejer's `DarejerController` exposes four helpers that produce a conventional response shape. Every Darejer-facing endpoint returns one of these so the frontend can speak a single shape across every screen.

---

## The four envelopes

### 1. `$this->json($data, $status = 200, $meta = [])`

The data envelope. Wraps a model / collection / paginator.

```php
// Paginator → unpacked into { data, meta }
$this->json(Product::paginate(15));
```

```json
{
  "data": [ {...}, {...}, ... ],
  "meta": {
    "total": 42,
    "current_page": 1,
    "last_page": 3,
    "per_page": 15,
    "from": 1,
    "to": 15
  }
}
```

```php
// Model → wrapped in { data }
$this->json($product);
```

```json
{ "data": { "id": 1, "name": "Widget", ... } }
```

```php
// Plain array → wrapped in { data }
$this->json(['id' => 1, 'name' => 'Thing']);
```

```json
{ "data": { "id": 1, "name": "Thing" } }
```

```php
// Pass extra meta:
$this->json($items, 200, ['cursor' => 'abc']);
// → { "data": [...], "meta": { "cursor": "abc" } }
```

### 2. `$this->jsonSuccess($message, $data = null, $status = 200)`

Action acknowledgement — bulk actions, status toggles, "OK"-style replies.

```php
$this->jsonSuccess('Archived', ['count' => 12]);
```

```json
{
  "success": true,
  "message": "Archived",
  "data": { "count": 12 }
}
```

When `$data` is `null`, it's omitted entirely.

### 3. `$this->jsonError($message, $status = 400, $errors = [])`

Generic error. Shape matches Laravel's validation bag so Inertia's `useForm` / `useHttp` surface it consistently.

```php
$this->jsonError('Cannot delete an active record', 422);
```

```json
{
  "message": "Cannot delete an active record",
  "errors": {}
}
```

When `$errors` is non-empty:

```php
$this->jsonError('Conflict', 409, [
    'sku' => ['SKU is already in use.'],
]);
```

```json
{
  "message": "Conflict",
  "errors": { "sku": ["SKU is already in use."] }
}
```

### 4. `$this->jsonValidation($errors, $message = 'The given data was invalid.')`

422 envelope, mirrors Laravel. Accepts a `Validator` instance OR a `[field => [messages]]` array.

```php
$validator = Validator::make($request->all(), [
    'email' => 'required|email',
]);

if ($validator->fails()) {
    return $this->jsonValidation($validator);
}
```

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

---

## When to use which

| Situation | Helper |
|---|---|
| Index/show JSON endpoint feeding a `DataGrid` / `Combobox` / etc. | `json()` |
| Bulk action / status toggle / "saved!" reply | `jsonSuccess()` |
| Validation failure | `jsonValidation()` |
| Authorization or business-rule failure | `jsonError($msg, 403\|422\|409)` |

Most CRUD `store` / `update` / `destroy` methods do **NOT** return JSON — they return Inertia redirects. The JSON helpers are for endpoints called from `useHttp`, which expects JSON.

---

## Note on paginator detection

`json()` checks `instanceof LengthAwarePaginator` for the auto-`{ data, meta }` unpacking. Cursor paginators and simple paginators flow through the model branch and need their meta passed manually:

```php
$this->json($cursorPaginator->items(), 200, [
    'next_cursor' => $cursorPaginator->nextCursor()?->encode(),
]);
```

---

## Related

- [`architecture/http-rules.md`](http-rules.md) — when frontend calls expect JSON.
- [`architecture/controller.md`](controller.md) — `DarejerController` and where these helpers come from.
