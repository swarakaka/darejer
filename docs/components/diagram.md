# Diagram

> Vue Flow-powered node-and-edge diagram. Useful for workflow visualizations, org charts, dependency graphs.

## Import / usage

```php
use Darejer\Components\Diagram;

Diagram::make('workflow')
    ->nodes($nodes)
    ->edges($edges)
    ->direction('LR')
```

## Props

Inherits the shared API from `BaseComponent`.

| Method | Type | Default | Description |
|---|---|---|---|
| `nodes($nodes)` | `array` | `[]` | Vue Flow node objects. |
| `edges($edges)` | `array` | `[]` | Vue Flow edge objects. |
| `dataUrl($url)` | `string` | `null` | Fetch nodes + edges from this URL. Returns `{ nodes, edges }`. |
| `readonly($flag = true)` | `bool` | `false` | Disable interaction. |
| `height($px)` | `int` | `400` | Canvas height. |
| `noMinimap()` | — | minimap `true` | Hide the minimap. |
| `noControls()` | — | controls `true` | Hide zoom/fit controls. |
| `noBackground()` | — | background `true` | Hide the dotted background. |
| `direction($dir)` | `string` | `'LR'` | Layout direction: `LR \| RL \| TB \| BT`. |

## Slots

None.

## Events

None on the backend — this is a read/visualize component. For diagram editing, populate it from a `dataUrl` and persist changes via your own actions.

## Node and edge shapes

The shapes follow Vue Flow's conventions:

```php
$nodes = [
    ['id' => '1', 'data' => ['label' => 'Lead'],     'position' => ['x' => 0,   'y' => 0]],
    ['id' => '2', 'data' => ['label' => 'Qualified'],'position' => ['x' => 200, 'y' => 0]],
];

$edges = [
    ['id' => 'e1-2', 'source' => '1', 'target' => '2'],
];
```

When `dataUrl` is set, the response should be `{ "nodes": [...], "edges": [...] }`.

## Examples

### Basic

```php
Diagram::make('flow')
    ->nodes([
        ['id' => '1', 'data' => ['label' => 'Start'], 'position' => ['x' => 0, 'y' => 0]],
        ['id' => '2', 'data' => ['label' => 'End'],   'position' => ['x' => 200, 'y' => 0]],
    ])
    ->edges([['id' => 'e1', 'source' => '1', 'target' => '2']])
```

### Intermediate — fetched from server

```php
Diagram::make('pipeline')
    ->dataUrl(route('pipeline.diagram'))
    ->direction('TB')
    ->height(600)
```

### Real-world — read-only org chart

```php
Diagram::make('org_chart')
    ->dataUrl(route('org.chart'))
    ->direction('TB')
    ->readonly()
    ->noBackground()
    ->height(800)
```

## Accessibility

- Vue Flow exposes nodes and edges as a graph — screen-reader support is limited; pair with a tabular alternative when graph content is essential.
- Pan/zoom are mouse-driven; provide a "reset view" affordance via the controls panel.

## Related

- [`Scheduler`](scheduler.md), [`Gantt`](gantt.md) — other dynamic visualizations.
