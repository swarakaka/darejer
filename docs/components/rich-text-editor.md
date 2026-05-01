# RichTextEditor

> TipTap-powered WYSIWYG editor. Configurable toolbar, height bounds, and character limit.

## Import / usage

```php
use Darejer\Components\RichTextEditor;

RichTextEditor::make('body')->minHeight(300)
```

## Props

Inherits the shared API from `BaseComponent`.

| Method | Type | Default | Description |
|---|---|---|---|
| `placeholder($text)` | `string` | `null` | Empty-state placeholder. |
| `minHeight($px)` | `int` | `200` | Minimum editor height in pixels. |
| `maxHeight($px)` | `int` | `0` (no max) | Maximum editor height; scrolls past. |
| `disabled($flag = true)` | `bool` | `false` | |
| `readonly($flag = true)` | `bool` | `false` | |
| `maxCharacters($n)` | `int` | `null` | Character cap (counts plain text, not markup). |
| `toolbar($tools)` | `string[]` | full toolbar | Restrict toolbar buttons. Common values: `['bold', 'italic', 'underline', 'link', 'bullet-list', 'ordered-list', 'heading-1', 'heading-2', 'code-block']`. |

## Slots

None.

## Events

None.

## Examples

### Basic

```php
RichTextEditor::make('description')
```

### Intermediate — minimal toolbar

```php
RichTextEditor::make('summary')
    ->toolbar(['bold', 'italic', 'link', 'bullet-list'])
    ->minHeight(150)
    ->maxCharacters(500)
```

### Real-world — article body

```php
Screen::make('Article')
    ->components([
        TextInput::make('title')->required(),
        RichTextEditor::make('body')
            ->placeholder('Start writing your article…')
            ->minHeight(400)
            ->maxHeight(800),
    ])
    ->render();
```

## Accessibility

- TipTap exposes ARIA roles for toolbar buttons; each is keyboard-reachable via `Tab`.
- Standard editing shortcuts: `Cmd/Ctrl+B`, `Cmd/Ctrl+I`, `Cmd/Ctrl+K` (link), `Cmd/Ctrl+Z` / `Cmd/Ctrl+Shift+Z`.
- The editor body has `role="textbox"` and `aria-multiline="true"`.

## Related

- [`Textarea`](textarea.md) — plain multi-line text.
