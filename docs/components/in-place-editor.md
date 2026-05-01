# InPlaceEditor

> Click-to-edit cell. Patches a single field on a record via the `darejer.data.update` endpoint.

## Import / usage

```php
use Darejer\Components\InPlaceEditor;

InPlaceEditor::make('product_name')
    ->field('name')
    ->updateUrl(route('darejer.data.update', ['model' => 'product', 'id' => $product->id]))
```

## Props

Inherits the shared API from `BaseComponent`.

| Method | Type | Default | Description |
|---|---|---|---|
| `field($field)` | `string` | `'name'` | Convenience: sets both `displayField` and `editField`. |
| `displayField($field)` | `string` | `'name'` | Field rendered when not editing. |
| `editField($field)` | `string` | `'name'` | Field sent to the backend in the PATCH payload. |
| `type($type)` | `string` | `'text'` | Editor type: `text`, `number`, `date`, `select`. |
| `select($options)` | `[value => label]` | — | Shortcut for `type('select')` with options. |
| `updateUrl($url)` | `string` | `null` | PATCH endpoint. |
| `disabled($flag = true)` | `bool` | `false` | |
| `placeholder($text)` | `string` | `null` | |

## Slots

None.

## Events

None on the backend — PATCH payload is `{ field, value }` and the standard `darejer.data.update` route handles it.

## How `field` is shaped to be `$fillable`-aware

`darejer.data.update` rejects fields that aren't in the model's `$fillable`. Make sure your model exposes the field:

```php
class Product extends Model
{
    protected $fillable = ['name', 'sku', /* … */];
}
```

For Spatie Translatable attributes, the value is auto-handled per-locale.

## Examples

### Basic — editable name

```php
InPlaceEditor::make('product_name')
    ->field('name')
    ->updateUrl(route('darejer.data.update', ['model' => 'product', 'id' => $product->id]))
```

### Intermediate — select-style in-place edit

```php
InPlaceEditor::make('order_status')
    ->field('status')
    ->select([
        'pending'   => 'Pending',
        'paid'      => 'Paid',
        'cancelled' => 'Cancelled',
    ])
    ->updateUrl(route('darejer.data.update', ['model' => 'order', 'id' => $order->id]))
```

### Real-world — number editor

```php
InPlaceEditor::make('stock')
    ->field('on_hand_qty')
    ->type('number')
    ->updateUrl(route('darejer.data.update', ['model' => 'stock', 'id' => $stockItem->id]))
    ->canSee('inventory.update')
```

## Accessibility

- The display element is a focusable `<button>`; pressing `Enter` or `Space` enters edit mode.
- The active editor is a labelled input/select; `Esc` cancels, `Enter` saves.
- Errors from the PATCH (e.g. forbidden field) surface inline.

## Related

- [`DataGrid`](data-grid.md) — for full table CRUD.
- [`EditableTable`](editable-table.md) — many cells in one form, before save.
