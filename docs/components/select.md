# SelectComponent

> Native shadcn-vue dropdown. Supports static option lists, backed-enum class strings, multi-select, and an optional searchable variant.
>
> For model-bound, server-paginated, or "addable" selects, use [`Combobox`](combobox.md).

## Import / usage

```php
use Darejer\Components\SelectComponent;

SelectComponent::make('status')
    ->options(['draft' => 'Draft', 'posted' => 'Posted'])
```

## Props

Inherits the shared API from `BaseComponent`.

| Method | Type | Default | Description |
|---|---|---|---|
| `options($options)` | `array\|class-string<BackedEnum>` | `[]` | `[value => label]` array OR a backed-enum class. Enum cases are read via `label()` instance method or static `options()`. |
| `placeholder($text)` | `string` | `null` | Placeholder when nothing is selected. |
| `disabled($flag = true)` | `bool` | `false` | |
| `searchable()` | — | `false` | Enable client-side filtering. |
| `multiple()` | — | `false` | Allow multiple selection (returns array). |

## Slots

None.

## Events

None.

## Examples

### Basic

```php
SelectComponent::make('priority')->options([
    'low'    => 'Low',
    'medium' => 'Medium',
    'high'   => 'High',
])
```

### Intermediate — backed enum

```php
// app/Enums/OrderStatus.php
enum OrderStatus: string
{
    case Draft = 'draft';
    case Posted = 'posted';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft     => 'Draft',
            self::Posted    => 'Posted',
            self::Cancelled => 'Cancelled',
        };
    }
}

// In the screen
SelectComponent::make('status')->options(OrderStatus::class)
```

### Real-world — multi-select with conditional

```php
SelectComponent::make('tags')
    ->multiple()
    ->searchable()
    ->options([
        'urgent'   => 'Urgent',
        'low-cost' => 'Low cost',
        'priority' => 'Priority',
    ])
    ->dependOn('type', 'service')
```

## Accessibility

- Renders shadcn-vue `<Select>` — full keyboard support: `↑/↓` to navigate, `Enter` to confirm, `Esc` to close, type-ahead matching.
- `searchable` switches to a `<Combobox>` shell with arrow-key navigation across filtered results.

## Related

- [`Combobox`](combobox.md) — searchable, model-bound, addable.
- [`RadioGroup`](radio-group.md) — visible option set.
- [`TagsInput`](tags-input.md) — free-form multi-value.
