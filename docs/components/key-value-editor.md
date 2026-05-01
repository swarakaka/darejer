# KeyValueEditor

> Editor for an array of `{key: value}` rows. Useful for arbitrary config maps, custom metadata, or HTTP headers.

## Import / usage

```php
use Darejer\Components\KeyValueEditor;

KeyValueEditor::make('metadata')->keyLabel('Header')->valueLabel('Value')
```

## Props

Inherits the shared API from `BaseComponent`.

| Method | Type | Default | Description |
|---|---|---|---|
| `keyLabel($text)` | `string` | `'Key'` | Column header for the key field. |
| `valueLabel($text)` | `string` | `'Value'` | Column header for the value field. |
| `disabled($flag = true)` | `bool` | `false` | |
| `max($n)` | `int` | `null` | Maximum number of rows. |
| `sortable()` | — | `false` | Allow drag-to-reorder. |

## Slots

None.

## Events

None.

## Examples

### Basic

```php
KeyValueEditor::make('options')
```

### Intermediate

```php
KeyValueEditor::make('http_headers')
    ->keyLabel('Header')
    ->valueLabel('Value')
    ->max(20)
    ->sortable()
```

### Real-world — webhook config

```php
Screen::make('Webhook')
    ->components([
        TextInput::make('endpoint')->url()->required(),
        KeyValueEditor::make('headers')
            ->keyLabel('Header')
            ->valueLabel('Value')
            ->hint('e.g. Authorization → Bearer xxx'),
    ])
    ->render();
```

## Accessibility

- Each row exposes "Add row" and "Remove row" buttons reachable via `Tab`.
- When `sortable`, rows have keyboard reorder controls (`↑` / `↓` buttons).

## Related

- [`TagsInput`](tags-input.md) — values without keys.
- [`Repeater`](repeater.md) — when each row is a structured component schema.
