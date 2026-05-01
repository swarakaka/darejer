# Kanban

> Drag-and-drop board bound to an Eloquent model. Each column maps to a value of a status field; dragging a card PATCHes that field via `/darejer/data/{model}/{id}`.

## Import / usage

```php
use Darejer\Components\Kanban;
use Darejer\Kanban\KanbanCard;
use Darejer\Kanban\KanbanColumn;
use Darejer\Routing\RoutePattern;

Kanban::make('opportunities')
    ->model(\App\Models\Opportunity::class)
    ->statusField('stage')
    ->columns([
        KanbanColumn::make('lead', 'Lead'),
        KanbanColumn::make('qualified', 'Qualified')->color('blue'),
        KanbanColumn::make('won', 'Won')->color('green')->locked(),
    ])
    ->card(
        KanbanCard::make()
            ->title('name')
            ->subtitle('customer_name')
            ->badge('priority')
    )
    ->editUrl(RoutePattern::row('opportunities.edit'), dialog: true)
```

## Props

Inherits the shared API from `BaseComponent`.

| Method | Type | Default | Description |
|---|---|---|---|
| `model($class)` | `class-string<Model>` | — | Bind to a model — auto-generates `dataUrl`. |
| `dataUrl($url)` | `string` | `null` | Override the data endpoint. |
| `columns($cols)` | [`KanbanColumn`](#kanbancolumn-api)[] | `[]` | Board columns. |
| `card($card)` | [`KanbanCard`](#kanbancard-api) | `null` | Card field bindings. |
| `statusField($field)` | `string` | `'status'` | Model field that determines the column. |
| `updateUrl($url)` | `string` | `null` | Override the PATCH URL. Default uses `/darejer/data/{model}/{id}`. |
| `editUrl($url, $dialog = false)` | `string`, `bool` | `null`, `false` | URL pattern (with `{id}`) opened on card click. Pass `dialog: true` to open as a modal. |
| `disabled($flag = true)` | `bool` | `false` | Disable drag-and-drop. |

## KanbanColumn API — `Darejer\Kanban\KanbanColumn`

| Method | Description |
|---|---|
| `make($value, $label)` | Static factory. `$value` matches the status-field value; `$label` is what users see. |
| `color($color)` | Tailwind color name — `red`, `amber`, `green`, `blue`, etc. |
| `limit($n)` | Visual WIP limit (rendered as a badge; not enforced server-side). |
| `locked()` | Mark column as drop-target-locked (no drops accepted). |

## KanbanCard API — `Darejer\Kanban\KanbanCard`

| Method | Description |
|---|---|
| `make()` | Static factory. |
| `title($field)` | Field for the card title. Default `name`. |
| `subtitle($field)` | Field for the subtitle line. |
| `badge($field)` | Field shown as a badge in the corner. |
| `meta($fields)` | Footer key/value rows. `[field => label]`. |
| `avatar($field)` | Field with an image URL or initials source. |
| `date($field)` | Field rendered as a date in the footer. |

## Slots

None.

## Events

None — drag-and-drop hits `/darejer/data/{model}/{id}` directly.

## Required model setup

For drag-and-drop to persist, the status field must be in the model's `$fillable`:

```php
class Opportunity extends Model
{
    protected $fillable = ['name', 'stage', /* … */];
}
```

## Examples

### Basic

```php
Kanban::make('tasks')
    ->model(\App\Models\Task::class)
    ->columns([
        KanbanColumn::make('todo', 'To do'),
        KanbanColumn::make('doing', 'Doing'),
        KanbanColumn::make('done', 'Done'),
    ])
```

### Intermediate — full card

```php
Kanban::make('issues')
    ->model(Issue::class)
    ->statusField('status')
    ->columns([
        KanbanColumn::make('triage', 'Triage')->color('gray')->limit(8),
        KanbanColumn::make('in_progress', 'In Progress')->color('blue')->limit(5),
        KanbanColumn::make('review', 'Review')->color('amber'),
        KanbanColumn::make('done', 'Done')->color('green')->locked(),
    ])
    ->card(
        KanbanCard::make()
            ->title('title')
            ->subtitle('repo_name')
            ->badge('priority')
            ->avatar('assignee_avatar')
            ->meta(['estimate' => 'Est.', 'due_at' => 'Due'])
            ->date('due_at')
    )
    ->editUrl(RoutePattern::row('issues.edit'), dialog: true)
```

### Real-world — disabled for non-admins

```php
Kanban::make('opportunities')
    ->model(Opportunity::class)
    ->statusField('stage')
    ->columns([/* … */])
    ->disabled(! auth()->user()->can('opportunities.update'))
```

## Accessibility

- Drag-and-drop is mouse/touch only. For keyboard users, `editUrl` opens an edit screen with a status `SelectComponent`/`Combobox` — provide that fallback.
- Each card is a focusable element with screen-reader announcements for column membership.

## Related

- [`DataGrid`](data-grid.md) — list view of the same data.
- [`Scheduler`](scheduler.md) — calendar view.
