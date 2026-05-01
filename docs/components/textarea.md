# Textarea

> Multi-line text input with configurable rows, optional auto-resize, and read-only / disabled states.

## Import / usage

```php
use Darejer\Components\Textarea;

Textarea::make('notes')->rows(6)
```

## Props

Inherits the shared API from `BaseComponent`.

| Method | Type | Default | Description |
|---|---|---|---|
| `placeholder($text)` | `string` | `''` | Placeholder. |
| `rows($n)` | `int` | `4` | Visible rows. |
| `readonly($flag = true)` | `bool` | `false` | |
| `disabled($flag = true)` | `bool` | `false` | |
| `autoResize()` | — | `false` | Grow with content. |

## Slots

None.

## Events

None.

## Examples

### Basic

```php
Textarea::make('description')->label('Description')
```

### Intermediate

```php
Textarea::make('notes')
    ->label('Internal notes')
    ->rows(8)
    ->autoResize()
    ->placeholder('Visible only to admins…')
```

### Real-world — support ticket

```php
Screen::make('New ticket')
    ->components([
        TextInput::make('subject')->required(),
        Textarea::make('body')
            ->label('Describe your issue')
            ->required()
            ->rows(10)
            ->autoResize(),
    ])
    ->actions([Actions\SaveAction::make()->url(route('tickets.store'))])
    ->render();
```

## Accessibility

- Renders a native `<textarea>` — supports keyboard newline (`Enter`) and screen-reader labels via `<FieldWrapper>`.
- `autoResize` adjusts height on input; users can still manually resize via the corner grip.

## Related

- [`TextInput`](text-input.md) — single-line.
- [`RichTextEditor`](rich-text-editor.md) — formatted text.
- [`TranslatableTextarea`](translatable-textarea.md) — locale-aware.
