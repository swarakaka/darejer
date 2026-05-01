# Translations

Darejer supports two distinct translation concerns:

1. **UI strings** — the language of buttons, labels, error messages.
2. **Model data** — translatable record attributes (e.g. a `Product`'s `name` in EN/AR/DE).

Both flow through the same `darejer.languages` config.

---

## 1. UI strings

### `__darejer($key)` helper

```php
// In a controller / Screen builder
Screen::make(__darejer('Products'))
    ->components([
        TextInput::make('name')->label(__darejer('Name'))->required(),
    ])
    ->render();
```

The helper looks up `darejer::darejer.{key}` in the package's `lang/` files (shipped: `en`, `ar`, `de`, `ckb`, `fa`, `fr`, `tr`). Host apps add or override translations by publishing them into `lang/vendor/darejer/{locale}/darejer.php`.

### Console commands

| Command | Purpose |
|---|---|
| `php artisan darejer:language` | Add or remove a language. Updates `config/darejer.php` + scaffolds language files. |
| `php artisan darejer:language:export` | Export all package strings into `darejer-translations.json`. |

### Active locale

Resolved per-request by `Darejer\Http\Middleware\HandleInertiaRequests` and shared as the Inertia prop `locale`. The topbar language switcher POSTs to `/darejer/locale` (`darejer.locale.update`), persisting the choice in the session.

### RTL

When the active locale is RTL (`ar`, `fa`, `ckb`, etc.), the middleware also shares `direction: 'rtl'`. The frontend root applies `dir="rtl"` and Tailwind's logical properties (`ps-*`, `me-*`, etc.) flip automatically.

> **Tailwind rule:** always use logical properties (`ps-*`, `pe-*`, `ms-*`, `me-*`) — never `pl-*`, `pr-*`, `ml-*`, `mr-*`. RTL breaks otherwise.

---

## 2. Translatable model attributes (Spatie)

### Model setup

```php
use Spatie\Translatable\HasTranslations;

class Product extends Model
{
    use HasTranslations;

    public array $translatable = ['name', 'description'];
}
```

### Components

Use `TranslatableInput` and `TranslatableTextarea` for translatable attributes:

```php
TranslatableInput::make('name')->required()
TranslatableTextarea::make('description')->rows(8)
```

These render multi-language UI **only when `darejer.languages` has more than one entry**. With a single language, they fall back to plain `TextInput` / `Textarea`.

### How values are shaped

`Screen::record($model)` automatically expands every translatable attribute into the full `{locale: value}` shape — every configured language is present, with empty strings for unset locales — so each tab/dialog renders consistently.

```json
{
  "name": {
    "en": "Hello",
    "ar": "مرحبا",
    "de": "Hallo"
  }
}
```

### Display

For read-only views (Show screens), use the `Display` component with `->translatable()`:

```php
Display::make('description')->translatable()->emptyText('—')
```

Or in a `DataGrid` column, set the model to use Spatie's accessors and display the resolved-locale string directly:

```php
Column::make('name')->searchable()  // shows the active locale's value
```

### Convenience trait

Pair `Spatie\Translatable\HasTranslations` with `Darejer\Concerns\HasDarejerTranslatable` for a few extra ergonomics:

```php
use Darejer\Concerns\HasDarejerTranslatable;
use Spatie\Translatable\HasTranslations;

class Product extends Model
{
    use HasDarejerTranslatable, HasTranslations;
}
```

---

## Configuring languages

```php
// config/darejer.php
'languages'        => ['en', 'ar', 'de', 'ckb'],
'default_language' => 'en',
```

This single array drives:

- Multi-language UI in `TranslatableInput` / `TranslatableTextarea`.
- The full `{locale: value}` expansion in `Screen::record()`.
- The Inertia shared prop `languages` available to every Vue page.
- Translations loaded by Laravel's translator (the package's `lang/{locale}/` directories register against the un-namespaced loader so validators pick up shipped messages).

---

## Related

- [`getting-started/configuration.md`](getting-started/configuration.md#languages-array) — `languages` config.
- [`components/translatable-input.md`](components/translatable-input.md), [`components/translatable-textarea.md`](components/translatable-textarea.md)
- [Spatie Translatable docs](https://github.com/spatie/laravel-translatable)
