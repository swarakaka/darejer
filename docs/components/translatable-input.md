# TranslatableInput

> Single-line text input bound to a [Spatie Translatable](https://github.com/spatie/laravel-translatable) attribute. Renders multi-language UI **only when `darejer.languages` has more than one entry** — falls back to a plain `TextInput` otherwise.

## Import / usage

```php
use Darejer\Components\TranslatableInput;

TranslatableInput::make('name')->required()
```

The bound model's column must be cast as a translatable array — see [Spatie's docs](https://github.com/spatie/laravel-translatable).

## Props

Inherits the shared API from `BaseComponent`.

| Method | Type | Default | Description |
|---|---|---|---|
| `placeholder($text)` | `string` | `''` | |
| `readonly($flag = true)` | `bool` | `false` | |
| `disabled($flag = true)` | `bool` | `false` | |
| `maxLength($n)` | `int` | `null` | |
| `autofocus()` | — | `false` | |

The `translatable: true` flag is always serialized so the frontend knows to expect/produce a `{locale: value}` object.

## Slots

None.

## Events

None.

## How the value is shaped

When `darejer.languages = ['en', 'ar', 'de']`, the field's value in the form payload looks like:

```json
{
  "name": {
    "en": "Hello",
    "ar": "مرحبا",
    "de": "Hallo"
  }
}
```

`Screen::record()` automatically expands the model's translatable attributes into the full `{locale: value}` shape — every configured language is present, with empty strings for unset locales — so the editor can render every tab consistently.

## Examples

### Basic

```php
TranslatableInput::make('title')->required()
```

### Intermediate

```php
TranslatableInput::make('slogan')
    ->label('Tagline')
    ->maxLength(120)
    ->placeholder('A short, snappy tagline.')
```

### Real-world — multi-language product

```php
class Product extends Model
{
    use \Spatie\Translatable\HasTranslations;

    public array $translatable = ['name', 'description'];
}

// In the controller
Screen::make('Edit Product')
    ->record($product)
    ->components([
        TranslatableInput::make('name')->required(),
        TranslatableTextarea::make('description'),
    ])
    ->render();
```

## Accessibility

- The default-locale input is the primary control; secondary locales open in a side dialog with full keyboard navigation.
- The dialog is a focus-trapped shadcn-vue `<Dialog>`.

## Related

- [`TranslatableTextarea`](translatable-textarea.md) — multi-line variant.
- [`TextInput`](text-input.md) — single-locale.
- [`languages` config](../getting-started/configuration.md#languages-array)
- [`translations.md`](../translations.md) — full translation strategy.
