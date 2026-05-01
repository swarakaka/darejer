# DatePicker

> Calendar-style date picker. Supports min/max dates, disabled dates, range selection, and custom display formats.

## Import / usage

```php
use Darejer\Components\DatePicker;

DatePicker::make('starts_at')->minDate(now()->toDateString())
```

## Props

Inherits the shared API from `BaseComponent`.

| Method | Type | Default | Description |
|---|---|---|---|
| `minDate($date)` | `string` | `null` | ISO date (`Y-m-d`). |
| `maxDate($date)` | `string` | `null` | ISO date. |
| `range()` | — | `false` | Select a start+end range; the field value becomes `[from, to]`. |
| `disabled($flag = true)` | `bool` | `false` | |
| `format($format)` | `string` | `'Y-m-d'` | PHP `date()` format used for display. |
| `placeholder($text)` | `string` | `null` | |
| `disabledDates($dates)` | `string[]` | `[]` | Specific dates to disable. |

## Slots

None.

## Events

None.

## Examples

### Basic

```php
DatePicker::make('birth_date')
```

### Intermediate — future dates only

```php
DatePicker::make('shipping_date')
    ->label('Shipping date')
    ->minDate(now()->toDateString())
    ->disabledDates([
        '2026-01-01',
        '2026-12-25',
    ])
```

### Real-world — date range filter

```php
DatePicker::make('period')
    ->label('Reporting period')
    ->range()
    ->minDate(now()->subYear()->toDateString())
    ->maxDate(now()->toDateString())
```

## Accessibility

- Built on `reka-ui` calendar — full keyboard support: `←/→` move by day, `↑/↓` by week, `PageUp/PageDown` by month, `Home/End` to jump to row edges.
- Day buttons are screen-reader-labelled with the full date.
- Disabled dates are announced as such.

## Related

- [`TimePicker`](time-picker.md) — time portion only.
- [`Display`](display.md) — for read-only formatted dates.
