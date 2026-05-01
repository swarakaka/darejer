# Scheduler

> FullCalendar-Vue3-powered calendar view. Streams events from a model's `dataUrl`, supports edit/create dialogs, multiple views, and per-event coloring.

## Import / usage

```php
use Darejer\Components\Scheduler;
use Darejer\Routing\RoutePattern;

Scheduler::make('appointments')
    ->model(\App\Models\Appointment::class)
    ->titleField('subject')
    ->startField('starts_at')
    ->endField('ends_at')
    ->editUrl(RoutePattern::row('appointments.edit'), dialog: true)
```

## Props

Inherits the shared API from `BaseComponent`.

| Method | Type | Default | Description |
|---|---|---|---|
| `model($class)` | `class-string<Model>` | — | Auto-generates `dataUrl` to `/darejer/data/{slug}?per_page=500`. |
| `dataUrl($url)` | `string` | `null` | Override the data endpoint. |
| `initialView($view)` | `string` | `'dayGridMonth'` | Starting view. |
| `views($views)` | `string[]` | `['dayGridMonth', 'timeGridWeek', 'timeGridDay', 'listWeek']` | Available views. |
| `readonly($flag = true)` | `bool` | `false` | Disable dragging/resizing/clicking. |
| `height($px)` | `int` | `500` | Calendar height. |
| `editUrl($url, $dialog = false)` | `string`, `bool` | `null`, `false` | URL pattern (with `{id}`) opened on event click. |
| `createUrl($url, $dialog = false)` | `string`, `bool` | `null`, `false` | URL opened on empty-slot click. |
| `titleField($field)` | `string` | `'title'` | Event field for the displayed text. |
| `startField($field)` | `string` | `'start'` | Event start datetime field. |
| `endField($field)` | `string` | `'end'` | Event end datetime field. |
| `colorField($field)` | `string` | `null` | Event field that supplies the color (CSS / hex). |
| `defaultColor($color)` | `string` | `null` | Color used when `colorField` is empty. |

## Slots

None.

## Events

None on the backend — clicks navigate to `editUrl` / `createUrl`. Drag-resize-persist is not yet wired in this version; use the edit dialog for changes.

## Examples

### Basic

```php
Scheduler::make('events')->model(Event::class)
```

### Intermediate — week view, custom fields

```php
Scheduler::make('shifts')
    ->model(Shift::class)
    ->initialView('timeGridWeek')
    ->views(['timeGridWeek', 'timeGridDay'])
    ->titleField('employee_name')
    ->startField('starts_at')
    ->endField('ends_at')
    ->colorField('color_hex')
    ->defaultColor('#3b82f6')
    ->height(700)
```

### Real-world — with edit + create dialogs

```php
Scheduler::make('appointments')
    ->model(Appointment::class)
    ->editUrl(RoutePattern::row('appointments.edit'), dialog: true)
    ->createUrl(route('appointments.create'), dialog: true)
    ->initialView('dayGridMonth')
```

## Accessibility

- FullCalendar provides keyboard navigation across the grid (`←/→/↑/↓` to move focus by day/week).
- Each event is a focusable element; pressing `Enter` opens its edit URL.

## Related

- [`Kanban`](kanban.md) — board view of the same data.
- [`Gantt`](gantt.md) — timeline view.
