# TagsInput

> Free-form or strict multi-value text input. Each comma-separated entry becomes a removable chip.

## Import / usage

```php
use Darejer\Components\TagsInput;

TagsInput::make('keywords')->suggestions(['urgent', 'priority'])
```

## Props

Inherits the shared API from `BaseComponent`.

| Method | Type | Default | Description |
|---|---|---|---|
| `suggestions($list)` | `string[]` | `[]` | Auto-completes matching tags as the user types. |
| `strict()` | — | `false` | Restrict input to values already in `suggestions()`. Free-form by default. |
| `max($n)` | `int` | `null` | Maximum number of tags. |
| `placeholder($text)` | `string` | `null` | |
| `disabled($flag = true)` | `bool` | `false` | |

`delimiter` defaults to `','` and is fixed for now.

## Slots

None.

## Events

None.

## Examples

### Basic

```php
TagsInput::make('skills')
```

### Intermediate — strict from suggestion list

```php
TagsInput::make('regions')
    ->strict()
    ->suggestions(['EU', 'NA', 'APAC', 'MENA'])
    ->max(3)
```

### Real-world — searchable keywords

```php
Screen::make('Article')
    ->components([
        TextInput::make('title')->required(),
        TagsInput::make('keywords')
            ->placeholder('Press Enter to add a keyword')
            ->suggestions($recentlyUsedKeywords),
    ])
    ->render();
```

## Accessibility

- Each chip is keyboard-removable via `Backspace` from the input or the chip's × button.
- Suggestions appear in a listbox with `↑/↓` navigation and `Enter` confirmation.
- The component announces tag additions/removals via an aria-live region.

## Related

- [`Combobox`](combobox.md) — when tags are model-bound.
- [`KeyValueEditor`](key-value-editor.md) — when each entry has a key+value.
