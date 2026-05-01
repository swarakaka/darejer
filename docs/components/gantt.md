# Gantt

> dhtmlx-gantt-powered timeline. Streams tasks from a `dataUrl`, supports critical-path display, progress bars, and configurable scale.

## Import / usage

```php
use Darejer\Components\Gantt;

Gantt::make('project_schedule')
    ->dataUrl(route('projects.gantt', $project))
    ->scale('week')
    ->showCriticalPath()
```

## Props

Inherits the shared API from `BaseComponent`.

| Method | Type | Default | Description |
|---|---|---|---|
| `dataUrl($url)` | `string` | `null` | Endpoint returning `{ data: [...task...], links: [...] }`. |
| `height($px)` | `int` | `500` | Chart height. |
| `readonly($flag = true)` | `bool` | `false` | Disable editing. |
| `scale($scale)` | `string` | `'week'` | Time scale: `'day' \| 'week' \| 'month' \| 'year'`. |
| `showCriticalPath()` | — | `false` | Highlight the critical path. |
| `noProgress()` | — | progress `true` | Hide progress bars on tasks. |
| `startDate($date)` | `string` | `null` | Force the chart's left bound (ISO date). |
| `endDate($date)` | `string` | `null` | Force the chart's right bound. |
| `textField($field)` | `string` | `'text'` | Task field used for the row label. |
| `startField($field)` | `string` | `'start_date'` | Task start field. |
| `endField($field)` | `string` | `'end_date'` | Task end field. |

`durationField` (`'duration'`) and `progressField` (`'progress'`) are fixed in this version (no public setter).

## Slots

None.

## Events

None on the backend currently — Gantt edits are not auto-persisted.

## Expected data shape

```json
{
  "data": [
    { "id": 1, "text": "Phase 1", "start_date": "2026-01-01", "duration": 14, "progress": 0.5 },
    { "id": 2, "text": "Phase 2", "start_date": "2026-01-15", "duration": 7,  "progress": 0 }
  ],
  "links": [
    { "id": 1, "source": 1, "target": 2, "type": "0" }
  ]
}
```

## Examples

### Basic

```php
Gantt::make('schedule')->dataUrl(route('projects.gantt'))
```

### Intermediate — month view, with critical path

```php
Gantt::make('roadmap')
    ->dataUrl(route('roadmap.gantt'))
    ->scale('month')
    ->showCriticalPath()
    ->height(700)
```

### Real-world — read-only

```php
Gantt::make('schedule')
    ->dataUrl(route('projects.gantt', $project))
    ->scale('week')
    ->readonly()
```

## Accessibility

- dhtmlx-gantt provides limited keyboard support; pair Gantt with a tabular task list for full accessibility.
- Gantt licensing: this package uses the GPL-licensed free build of dhtmlx-gantt. For commercial host apps, ensure your distribution model is compatible with GPL.

## Related

- [`Scheduler`](scheduler.md) — calendar variant.
- [`DataGrid`](data-grid.md) — tabular variant.
