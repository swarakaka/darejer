# Screen Engine

A **Screen** is the unit of UI in Darejer. It declares title, components, actions, record, breadcrumbs, sections/tabs — all in PHP — and renders through a single generic `Screen.vue` page.

You never write a Vue file per screen.

---

## The flow

```
Controller method
    ↓
Screen::make()
    ->record(...)
    ->components([...])
    ->actions([...])
    ->render()
    ↓
Inertia::render('Screen', $serializedSchema)
    ↓
Screen.vue receives the schema as page props and paints the screen
```

---

## Builder API

```php
use Darejer\Screen\Screen;
use Darejer\Screen\Section;
use Darejer\Screen\Tab;

Screen::make('Edit Product')
    ->record($product)
    ->breadcrumbs([
        ['label' => 'Products', 'url' => route('products.index')],
        ['label' => $product->name],
    ])
    ->components([/* Components\* instances */])
    ->actions([/* Actions\* instances */])
    ->sections([/* Section instances */])
    ->tabs([/* Tab instances */])
    ->fullWidth()
    ->dialog('lg')          // optional dialog mode
    ->with(['extra' => 1])  // any extra Inertia props
    ->render();             // → Inertia\Response
```

| Method | Type | Purpose |
|---|---|---|
| `make($title)` | `static` | Factory. Sets the screen title. |
| `title($title)` | `static` | Override the title. |
| `record($model\|$array\|null)` | `static` | The Eloquent model or attribute array to bind fields against. Spatie Translatable attributes are auto-expanded into the full `{locale: value}` shape for every configured language. |
| `breadcrumbs($crumbs)` | `static` | `[['label' => '…', 'url' => '…'], ...]`. Read by `<AppBreadcrumbs>`. |
| `components($components)` | `static` | Array of `Componentable` instances. |
| `actions($actions)` | `static` | Array of `Actionable` instances. |
| `sections($sections)` | `static` | FastTab-style collapsible groups. Each `Section` lists field names. |
| `tabs($tabs)` | `static` | Horizontal tab bar. Each `Tab` lists field names. |
| `dialog($size = 'md')` | `static` | Render as a modal. Sizes: `xs\|sm\|md\|lg\|xl\|full`. |
| `fullWidth($flag = true)` | `static` | Stretch screen to full container width. |
| `with($props)` | `static` | Merge any extra Inertia props onto the page. |
| `render()` | `Inertia\Response` | Serialize and return. |

---

## Inertia props shape

The page receives:

```json
{
  "title": "Edit Product",
  "dialog": false,
  "dialogSize": "md",
  "record": { "id": 1, "name": "Widget", ... },
  "components": [
    { "type": "TextInput", "name": "name", "label": "Name", "required": true, ... },
    { "type": "Combobox",  "name": "category_id", "dataUrl": "/darejer/data/category", ... }
  ],
  "actions": [
    { "type": "Save",   "label": "Save",   "url": "/products/1", "method": "PUT" },
    { "type": "Cancel", "label": "Cancel", "url": "/products" }
  ],
  "breadcrumbs": [{ "label": "Products", "url": "/products" }, { "label": "Widget" }],
  "sections": null,
  "tabs": null,
  "fullWidth": null,
  "errors": {},
  "flash": null
}
```

`errors` and `flash` come from the Inertia HandleInertiaRequests middleware shared props.

---

## Components — `Componentable`

Every component implements `Darejer\Screen\Contracts\Componentable`:

```php
public function toArray(): ?array;
```

Returning `null` strips the component from the payload. `BaseComponent` does this automatically when `canSee()` denies the user.

`PermissionGuard` returns a `__guard__` marker; `Screen::serializeComponents()` flattens its children into the top-level component list. See [`components/permission-guard.md`](../components/permission-guard.md).

## Actions — `Actionable`

Every action implements `Darejer\Screen\Contracts\Actionable`:

```php
public function toArray(): ?array;
```

Same null-stripping behavior as components.

---

## Dialog mode

Any screen can be rendered as a modal by adding `->dialog()`:

```php
public function edit(Product $product)
{
    return Screen::make('Edit Product')
        ->dialog()                     // ← that's it
        ->record($product)
        ->components($this->fields())
        ->actions([...])
        ->render();
}
```

The Inertia payload now carries `"dialog": true` and `Screen.vue` wraps the body in a shadcn-vue `<Dialog>`. The route, controller, and method are unchanged — dialog is purely a rendering mode.

To open a screen as a dialog from another screen, mark the linking action:

```php
Actions\ButtonAction::make('Edit')
    ->dialog()
    ->url(route('products.edit', $product));
```

The frontend opens `/products/{id}/edit` in a dialog overlay rather than navigating.

> **The `Combobox` inline-create dialog uses a slightly different mechanism**: it fetches a `Form` JSON schema via `useHttp` from `GET /{resource}/forms/{name}`. See [`components/combobox.md`](../components/combobox.md) and [`architecture/controller.md`](controller.md#forms--forms-method).

---

## Sections (FastTab) — `Darejer\Screen\Section`

Group fields into collapsible cards. Each section lists field **names** — the actual component definitions still live in `->components([...])`.

```php
use Darejer\Screen\Section;

Screen::make('Edit Customer')
    ->components([
        Components\TextInput::make('name'),
        Components\TextInput::make('email'),
        Components\TextInput::make('billing_street'),
        Components\TextInput::make('billing_city'),
    ])
    ->sections([
        Section::make('General')->components(['name', 'email']),
        Section::make('Billing')
            ->components(['billing_street', 'billing_city'])
            ->collapsed(),
    ])
    ->render();
```

| Method | Purpose |
|---|---|
| `make($title)` | Factory. |
| `title($title)` | Rename. |
| `components($names)` | Field names belonging to this section. |
| `collapsed($flag = true)` | Start collapsed. |
| `alwaysExpanded($flag = true)` | Suppress the collapse toggle. |
| `dependOn($rule)` | `{field, operator, value}` — show only when the rule matches. |

## Tabs — `Darejer\Screen\Tab`

Same pattern as sections, rendered as a horizontal tab bar above the body:

```php
use Darejer\Screen\Tab;

Screen::make('Product')
    ->components([/* … all fields … */])
    ->tabs([
        Tab::make('Details')->components(['name', 'sku', 'price']),
        Tab::make('Inventory')->components(['stock', 'min_stock']),
        Tab::make('Media')->components(['gallery']),
    ])
    ->render();
```

| Method | Purpose |
|---|---|
| `make($title)` | Factory. |
| `title($title)` | Rename. |
| `components($names)` | Field names belonging to this tab. |
| `dependOn($rule)` | Conditional visibility. |

---

## Reusable forms — `Darejer\Forms\Form`

A `Form` is a Screen-builder twin you can reuse across create/edit screens AND publish at `GET /{resource}/forms/{name}` for inline-dialog consumers (the `Combobox` create-affordance).

```php
public function forms(): array
{
    return [Form::make('default')->components([/* … */])];
}

public function create()
{
    return $this->form()
        ->title(__('New Product'))
        ->record(new Product)
        ->save(route('products.store'), 'POST')
        ->renderAsScreen();
}
```

`Form` mirrors `Screen` (components, actions, record, sections, tabs, breadcrumbs, fullWidth, with) plus:

| Method | Purpose |
|---|---|
| `save($url, $method = 'POST', $label = null)` | Add a `SaveAction` — also picks the label. |
| `cancel($url = null, $label = null)` | Add a `CancelAction`. Pass `null` URL to use the default. |
| `cancelLabel($label)` | Override the cancel label only. Pass `null` to suppress the cancel button. |
| `addActions($actions)` | Append more actions (e.g. `DeleteAction`). |
| `actions($actions)` | Replace the entire action toolbar. |
| `toArray()` | JSON shape for `useHttp` consumers. |
| `renderAsScreen()` | Full Inertia page. Used by `create()` / `edit()` controllers. |

See [`api-reference/php-api.md`](../api-reference/php-api.md) for the full method catalog.

---

## Related

- [`architecture/controller.md`](controller.md) — how controllers feed into screens.
- [`architecture/http-rules.md`](http-rules.md) — how the frontend talks back.
- [`components/README.md`](../components/README.md) — every component you can drop into `->components([...])`.
- [`actions/README.md`](../actions/README.md) — every action you can drop into `->actions([...])`.
