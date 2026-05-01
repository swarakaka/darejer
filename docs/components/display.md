# Display

> Read-only field that renders its value with the right element for the type — colored badges for enum statuses, formatted dates, formatted numbers / money, Yes/No badges for booleans. Never a disabled input.
>
> Use `Display` on **show screens** instead of `TextInput::make()->readonly()`.

## Import / usage

```php
use Darejer\Components\Display;

Display::make('status')->badge([
    'draft'  => 'neutral',
    'posted' => 'success',
])
```

## Props

Inherits the shared API from `BaseComponent`.

### Type setters (mutually exclusive)

| Method | Description |
|---|---|
| `badge($colorMap, $labels = null)` (implicit via the `badge` factory below) | — |
| `date($format = 'Y-m-d')` | Render as a date. |
| `dateTime($format = 'Y-m-d H:i')` | Render as a date+time. |
| `number($decimals = 0)` | Localized number. |
| `money($decimals = 2, $currencyField = null)` | Localized number with optional trailing currency code from `$currencyField` on the record. |
| `boolean($trueLabel = null, $falseLabel = null)` | Yes/No badge. Labels default to translated `Yes` / `No`. |

### `badge($colorMap)`

Render the value as a colored badge.

| Argument | Type | Description |
|---|---|---|
| `$colorMap` | `array<string, string>` OR `class-string<BackedEnum>` | Map of value → variant (`success \| warning \| danger \| info \| neutral`), or a backed-enum exposing per-case colors via `color()` or static `colors()`. |

When a backed-enum class is given, labels are also auto-derived from the enum (via `label()` or static `options()`).

### Decoration

| Method | Description |
|---|---|
| `prefix($text)` | Prepend text. |
| `suffix($text)` | Append text. |
| `emptyText($text)` | Placeholder when value is empty. Default `—`. |
| `translatable($flag = true)` | Treat the value as a translatable JSON object (`{en, ar, …}`); the frontend resolves the active locale. Use for plain text or badges whose underlying column is cast as a translatable array. |

## Slots

None.

## Events

None.

## Examples

### Basic — badge from array

```php
Display::make('status')->badge([
    'draft'     => 'neutral',
    'posted'    => 'success',
    'cancelled' => 'danger',
])
```

### Intermediate — backed enum

```php
enum DocumentStatus: string
{
    case Draft = 'draft';
    case Posted = 'posted';
    case Cancelled = 'cancelled';

    public function label(): string  { /* … */ }
    public function color(): string  { return match ($this) {
        self::Posted    => 'success',
        self::Cancelled => 'danger',
        default         => 'neutral',
    }; }
}

Display::make('status')->badge(DocumentStatus::class)
```

### Real-world — show screen

```php
Screen::make('Order')
    ->record($order)
    ->components([
        Display::make('reference'),
        Display::make('placed_at')->dateTime(),
        Display::make('customer_name'),
        Display::make('total')->money(2, currencyField: 'currency_code'),
        Display::make('status')->badge(OrderStatus::class),
        Display::make('is_paid')->boolean(),
        Display::make('description')->translatable()->emptyText('No description provided.'),
    ])
    ->render();
```

## Accessibility

- Each value is rendered with the most semantic element for the type — `<time>` for dates, `<span class="badge">` for badges, plain text for numbers/money.
- Empty values render the `emptyText` placeholder so screen readers don't encounter empty containers.

## Related

- [`TextInput`](text-input.md) — interactive variant.
- [`DataGrid` Column](data-grid.md#column-api) — same display vocabulary in tables.
