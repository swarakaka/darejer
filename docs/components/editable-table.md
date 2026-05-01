# EditableTable

> Inline editor for rows of a sub-array on the surrounding form. Each column declares its input type — text, number, date, checkbox, select, or `combobox`. Combobox cells can auto-fill sibling cells from the selected record.
>
> Use this for invoice lines, BOM rows, time-tracking entries — anywhere you want a spreadsheet-style sub-form.

## Import / usage

```php
use Darejer\Components\EditableTable;
use Darejer\EditableTable\Column;

EditableTable::make('lines')
    ->columns([
        Column::make('item_id')
            ->label('Item')
            ->combobox(\App\Models\Item::class)
            ->showPrice('selling_price')
            ->fillFrom([
                'rate'   => 'selling_price',
                'uom_id' => 'default_uom_id',
            ]),
        Column::make('qty')->number()->width('6rem'),
        Column::make('rate')->number()->width('8rem'),
    ])
```

## Props

Inherits the shared API from `BaseComponent`.

| Method | Type | Default | Description |
|---|---|---|---|
| `columns($cols)` | [`Column`](#column-api)[] OR legacy assoc-array[] | `[]` | Column definitions. |
| `addable($flag = true)` | `bool` | `true` | Show "Add row". |
| `deletable($flag = true)` | `bool` | `true` | Show per-row delete. |
| `sortable($flag = true)` | `bool` | `false` | Drag rows to reorder. |
| `disabled($flag = true)` | `bool` | `false` | Read-only mode. |
| `maxRows($n)` | `int` | `null` | Hard row cap. |
| `defaultRow($defaults)` | `array` | `[]` | Defaults for new rows. |

`fullWidth` defaults to `true` for this component (it reads better as a full-row).

## Column API — `Darejer\EditableTable\Column`

Static factory `Column::make($field)`.

| Method | Description |
|---|---|
| `label($text)` | Override label. |
| `width($width)` | CSS width. |
| `disabled($flag = true)` | Read-only column. |
| `placeholder($text)` | Cell placeholder. |
| `text()` / `number()` / `date()` / `checkbox()` | Set input type. |
| `select($options)` | Static select cell. `[value => label]`. |
| `combobox($modelClass, $key = 'id', $label = 'name')` | Searchable combobox cell — auto-binds to `/darejer/data/{slug}`. |
| `dataUrl($url)` | Override the combobox's data URL. |
| `showPrice($field = 'price')` | Show this field's value beside each combobox option (e.g. unit price). |
| `showImage($field = 'image')` | Show this field's image beside each option. |
| `fillFrom($mapping)` | On combobox select, copy fields from the chosen record into sibling cells. `[rowColumnField => recordField]`. |

## Slots

None.

## Events

None on the backend — the value is bound to the surrounding form payload as an array.

## Examples

### Basic — invoice lines

```php
EditableTable::make('lines')
    ->columns([
        Column::make('description')->placeholder('Service / item'),
        Column::make('qty')->number()->width('6rem'),
        Column::make('rate')->number()->width('8rem'),
    ])
    ->defaultRow(['qty' => 1])
```

### Intermediate — with combobox auto-fill

```php
EditableTable::make('lines')
    ->columns([
        Column::make('item_id')
            ->label('Item')
            ->combobox(Item::class)
            ->showImage('image')
            ->showPrice('selling_price')
            ->fillFrom([
                'rate'        => 'selling_price',
                'uom_id'      => 'default_uom_id',
                'description' => 'name',
            ])
            ->width('20rem'),

        Column::make('description'),
        Column::make('qty')->number()->width('6rem'),
        Column::make('uom_id')->select($uomOptions)->width('8rem'),
        Column::make('rate')->number()->width('8rem'),
    ])
    ->sortable()
```

### Real-world — disabled when locked

```php
EditableTable::make('lines')
    ->columns([/* … */])
    ->disabled($order->is_locked)
```

## Accessibility

- Each cell is a labelled input reachable via `Tab` (left-to-right, top-to-bottom).
- Add-row and delete-row buttons are keyboard-reachable.
- When `sortable`, drag handles double as keyboard reorder buttons.

## Related

- [`Repeater`](repeater.md) — when each "row" is a structured component schema (forms within a form).
- [`Combobox`](combobox.md) — single-value variant of the combobox cell.
- [`KeyValueEditor`](key-value-editor.md) — simpler 2-column key/value editor.
