# TimePicker

> Hour/minute (and optional seconds) picker with 12- or 24-hour modes and configurable step.

## Import / usage

```php
use Darejer\Components\TimePicker;

TimePicker::make('opens_at')->step(15)
```

## Props

Inherits the shared API from `BaseComponent`.

| Method | Type | Default | Description |
|---|---|---|---|
| `withSeconds()` | — | `false` | Include the seconds picker. |
| `hour12()` | — | `false` (24h) | Use 12-hour AM/PM mode. |
| `disabled($flag = true)` | `bool` | `false` | |
| `minTime($time)` | `string` | `null` | E.g. `'09:00'`. |
| `maxTime($time)` | `string` | `null` | E.g. `'17:00'`. |
| `placeholder($text)` | `string` | `null` | |
| `step($minutes)` | `int` | `1` | Minute step. |

## Slots

None.

## Events

None.

## Examples

### Basic

```php
TimePicker::make('start_time')
```

### Intermediate — business hours, 15-min step

```php
TimePicker::make('appointment_at')
    ->minTime('09:00')
    ->maxTime('17:00')
    ->step(15)
```

### Real-world — 12-hour with seconds

```php
TimePicker::make('logged_at')
    ->hour12()
    ->withSeconds()
    ->placeholder('e.g. 02:15:30 PM')
```

## Accessibility

- Hour, minute, and (optional) second segments are individually focusable; `↑/↓` increments by `step`.
- 12-hour mode exposes an AM/PM toggle reachable via `Tab`.
- All segments have screen-reader labels.

## Related

- [`DatePicker`](date-picker.md) — date portion only.
