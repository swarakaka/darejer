# TextInput

> Single-line text input with switchable HTML input type, optional prefix/suffix, character limits, and read-only / disabled states.

## Import / usage

```php
use Darejer\Components\TextInput;

TextInput::make('email')->email()->required()
```

## Props

Inherits the shared API from `BaseComponent` (`label`, `required`, `hint`, `tooltip`, `default`, `fullWidth`, `dependOn*`, `canSee`).

| Method | Type | Default | Description |
|---|---|---|---|
| `placeholder($text)` | `string` | `''` | Placeholder text. |
| `type($type)` | `string` | `'text'` | One of `text\|email\|password\|number\|url\|tel`. |
| `email()` | — | — | Shortcut for `->type('email')`. |
| `password()` | — | — | Shortcut for `->type('password')`. |
| `number()` | — | — | Shortcut for `->type('number')`. |
| `url()` | — | — | Shortcut for `->type('url')`. |
| `tel()` | — | — | Shortcut for `->type('tel')`. |
| `readonly($flag = true)` | `bool` | `false` | |
| `disabled($flag = true)` | `bool` | `false` | |
| `maxLength($n)` | `int` | `null` | HTML `maxlength` attribute. |
| `prefix($text)` | `string` | `null` | Leading slot text (e.g. `$`, `https://`). |
| `suffix($text)` | `string` | `null` | Trailing slot text (e.g. `kg`). |
| `autofocus()` | — | `false` | Autofocus on mount. |

## Slots

None — content is fully driven by props.

## Events

None on the backend — value is bound to the surrounding form state and submitted with the parent action.

## Examples

### Basic

```php
TextInput::make('name')->label('Name')->required()
```

### Intermediate

```php
TextInput::make('price')
    ->label('Price')
    ->number()
    ->prefix('$')
    ->required()
    ->hint('Inclusive of tax.')
```

### Real-world — login form

```php
Screen::make('Sign in')
    ->components([
        TextInput::make('email')
            ->label(__('Email'))
            ->email()
            ->required()
            ->autofocus(),
        TextInput::make('password')
            ->label(__('Password'))
            ->password()
            ->required(),
    ])
    ->actions([
        Actions\SaveAction::make(__('Sign in'))
            ->url(route('login'))
            ->method('POST'),
    ])
    ->render();
```

## Accessibility

- Renders a native `<input>` — supports keyboard tab order and screen-reader labels via `<FieldWrapper>`.
- `prefix` / `suffix` are decorative and `aria-hidden`.
- `password` type masks input by default; the surrounding wrapper provides a "show password" toggle.

## Related

- [`Textarea`](textarea.md) — multi-line.
- [`TranslatableInput`](translatable-input.md) — locale-aware.
- [`Combobox`](combobox.md) — when you need select+search.
