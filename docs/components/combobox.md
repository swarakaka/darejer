# Combobox

> Searchable, model-bound select. Streams options from the server via `dataUrl`, supports composite labels, custom search fields, an inline-create dialog ("addable"), and form prefill on selection.

The Combobox is the workhorse for any `BelongsTo` relation in a Darejer form.

## Import / usage

```php
use Darejer\Components\Combobox;
use App\Models\Category;

Combobox::make('category_id')->model(Category::class)
```

## Props

Inherits the shared API from `BaseComponent`.

| Method | Type | Default | Description |
|---|---|---|---|
| `model($class, $key = 'id', $label = 'name', $query = null)` | `class-string<Model>`, `string`, `string\|array\|Closure`, `Closure?` | — | Bind to an Eloquent model. Auto-generates `dataUrl` and `addUrl`. `$label` may be a single field, an array of fields (composite label `code — name`), or a closure for full control. `$query` scopes the underlying query. |
| `options($options)` | `array<string,string>` | — | Provide static `[value => label]` instead of a model. Disables `dataUrl`. |
| `dataUrl($url)` | `string` | `null` | Override the data endpoint. |
| `addUrl($url)` | `string` | `null` | Override the create-screen URL used by `->addable()`. |
| `addable($flag = true)` | `bool` | `false` | Render an "Add new…" affordance below options. Opens the bound model's create screen as a dialog. |
| `createForm($name)` | `string` | — | Bind the "Add new…" affordance to a named [`Form`](../architecture/screen-engine.md#reusable-forms--darejerformsform) on the bound model's controller. Implies `addable()`. Frontend fetches the form schema via `useHttp` from `GET /{resource}/forms/{name}` — no Inertia navigation. |
| `multiple($flag = true)` | `bool` | `false` | Multi-select. |
| `searchable($flag = true)` | `bool` | `true` | Enable search input. |
| `clearable($flag = true)` | `bool` | `true` | Show the × clear button. |
| `disabled($flag = true)` | `bool` | `false` | |
| `placeholder($text)` | `string` | `null` | |
| `searchFields($fields)` | `string[]` | `null` | Override which fields the server-side search runs against. Defaults to the label fields. |
| `prefillFrom($url)` | `string` | `null` | On selection, fetch `$url?id=<chosen-id>` and merge the response into the surrounding form. Lets create screens prefill related fields (e.g. line items) without changing the URL. The endpoint must return a flat `{field: value}` object whose keys match form-field names. No-op for multi-select. |

## Slots

None.

## Events

None on the backend — selection is bound to the surrounding form.

## Examples

### Basic — bound to a model

```php
Combobox::make('category_id')->model(Category::class)
```

The component automatically uses `name` as the label and queries `/darejer/data/category`.

### Intermediate — composite label + scoped query

```php
Combobox::make('product_id')
    ->label('Product')
    ->model(
        Product::class,
        key: 'id',
        label: ['sku', 'name'],          // → "ABC-100 — Widget"
        query: fn ($q) => $q->where('is_active', true),
    )
    ->searchFields(['sku', 'name', 'barcode'])
    ->required()
```

### Static options

```php
Combobox::make('country_code')
    ->options([
        'US' => 'United States',
        'GB' => 'United Kingdom',
        'IQ' => 'Iraq',
    ])
```

### Inline-create via `Form` (no navigation)

```php
// On the related controller:
public function forms(): array
{
    return [
        Form::make('quick-create')->components([
            Components\TextInput::make('name')->required(),
            Components\TextInput::make('contact_email')->email(),
        ]),
    ];
}

// On the screen using the Combobox:
Combobox::make('customer_id')
    ->model(Customer::class)
    ->createForm('quick-create')
```

The "Add" button now opens an inline dialog rendered from the form schema fetched via `useHttp` from `GET /customers/forms/quick-create`.

### Prefill the surrounding form

A common pattern in line-item screens — picking a Sales Order should prefill its lines:

```php
Combobox::make('source_order_id')
    ->label(__('Source order'))
    ->model(SalesOrder::class)
    ->prefillFrom(route('sales-orders.prefill'))
```

```php
// Controller endpoint
#[Route('GET', 'prefill', name: 'prefill')]
public function prefill(Request $request)
{
    $order = SalesOrder::with('lines')->findOrFail($request->integer('id'));

    return $this->json([
        'customer_id' => $order->customer_id,
        'currency'    => $order->currency,
        'lines'       => $order->lines->toArray(),
    ]);
}
```

## Accessibility

- Renders shadcn-vue `<Combobox>` — keyboard `↑/↓` to navigate options, `Enter` to confirm, `Esc` to close, `Backspace` clears (when `clearable`).
- Search input is a labelled `<input role="combobox">` with `aria-expanded` and `aria-activedescendant`.
- The "Add new…" button is reachable via `Tab` after the option list.

## Related

- [`SelectComponent`](select.md) — for short, static option lists.
- [`TagsInput`](tags-input.md) — free-form multi-value.
- [`EditableTable`](editable-table.md) — combobox cells in repeating rows.
- [`architecture/screen-engine.md`](../architecture/screen-engine.md#reusable-forms--darejerformsform) — `Form` definition.
