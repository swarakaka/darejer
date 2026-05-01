# Repeater

> Repeating sub-form. Each "item" is rendered with a full component schema — useful for nested address blocks, line items with conditionals, or arbitrarily complex structured arrays.
>
> For pure tabular line-items, prefer [`EditableTable`](editable-table.md) — it's denser. Use Repeater when you need full form features per row (validation, dependOn, file uploads, rich text…).

## Import / usage

```php
use Darejer\Components\Repeater;
use Darejer\Components\TextInput;
use Darejer\Components\Combobox;

Repeater::make('addresses')
    ->itemLabel('Address')
    ->itemLabelField('label')
    ->schema([
        TextInput::make('label')->required(),
        Combobox::make('country_id')->model(Country::class),
        TextInput::make('street'),
        TextInput::make('city'),
    ])
    ->minItems(1)
```

## Props

Inherits the shared API from `BaseComponent`.

| Method | Type | Default | Description |
|---|---|---|---|
| `schema($components)` | `Componentable[]` | `[]` | Components rendered for each item. |
| `addable($flag = true)` | `bool` | `true` | Show "Add" button. |
| `deletable($flag = true)` | `bool` | `true` | Show per-item delete. |
| `sortable($flag = true)` | `bool` | `false` | Drag-to-reorder. |
| `collapsed($flag = true)` | `bool` | `false` | Start with all items collapsed. |
| `maxItems($n)` | `int` | `null` | Hard cap. |
| `minItems($n)` | `int` | `null` | Soft minimum (validation in your controller). |
| `addLabel($text)` | `string` | `'Add item'` | Label on the add button. |
| `itemLabel($text)` | `string` | `'Item'` | Default header text per item — `Item 1`, `Item 2`. |
| `itemLabelField($field)` | `string` | `null` | Field within an item whose value becomes the header. Falls back to `itemLabel` + index. |
| `defaultItem($defaults)` | `array` | `[]` | Initial values for newly-added items. |

## Slots

None.

## Events

None on the backend.

## Examples

### Basic

```php
Repeater::make('phone_numbers')
    ->schema([
        SelectComponent::make('type')->options([
            'mobile' => 'Mobile', 'work' => 'Work', 'home' => 'Home',
        ]),
        TextInput::make('number')->tel(),
    ])
```

### Intermediate — sortable, with default

```php
Repeater::make('lines')
    ->itemLabel('Line')
    ->itemLabelField('description')
    ->schema([
        TextInput::make('description')->required(),
        TextInput::make('qty')->number()->required()->default(1),
        TextInput::make('rate')->number()->required(),
        Toggle::make('taxable'),
    ])
    ->sortable()
    ->defaultItem(['qty' => 1, 'taxable' => true])
    ->minItems(1)
    ->maxItems(50)
```

### Real-world — nested with conditional

```php
Repeater::make('attendees')
    ->itemLabelField('name')
    ->schema([
        TextInput::make('name')->required(),
        TextInput::make('email')->email()->required(),
        SelectComponent::make('role')->options([
            'guest' => 'Guest', 'speaker' => 'Speaker',
        ]),
        Textarea::make('bio')
            ->dependOn('role', 'speaker')
            ->rows(3),
    ])
```

## Accessibility

- Each item is a `<section>` with the item label as its heading.
- Drag handles (when `sortable`) double as keyboard reorder buttons.
- Add/delete buttons are reachable via `Tab` and announce changes via aria-live.

## Related

- [`EditableTable`](editable-table.md) — denser tabular variant.
- [`KeyValueEditor`](key-value-editor.md) — single key/value pairs.
