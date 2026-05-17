# Darejer

> Write only PHP. Get a complete enterprise frontend.

Darejer is a Laravel package that lets you build full-featured admin screens, CRUD interfaces, dashboards, and ERP/CRM modules **entirely in PHP** — no Vue, no JavaScript, no frontend work.

The package serializes your PHP screen definitions to Inertia page props, which are rendered by a set of pre-built Vue 3 components powered by shadcn-vue, Tailwind CSS v4, and Inertia v3.

---

## Features

- **Auth out of the box** — Laravel Fortify wired to Inertia pages (login, forgot/reset password, 2FA challenge, email verification, confirm password)
- **`DarejerController` base** — controllers extend one class; REST routes auto-register, `#[Route]` attribute for custom endpoints, standard JSON envelope helpers — nothing to add to `routes/web.php`
- **Screen engine** — define pages, forms, and actions entirely in PHP
- **30+ components** — DataGrid, Kanban, Gantt, Scheduler, Diagram, RichTextEditor, Signature, Repeater, and more
- **FastTab layout** — collapsible accordion sections like Microsoft Dynamics 365
- **Translatable fields** — Spatie Translatable integration with multi-language UI
- **Permission system** — Spatie Permissions with super-admin bypass and `canSee()` on every component
- **DataTable** — server-side pagination, sorting, filtering from a single PHP class
- **Form system** — Inertia v3 `useForm`, validation errors, file uploads, dirty state
- **dependOn** — conditional component visibility with 11 operators + cascading reset
- **Navigation** — PHP-defined sidebar nav with groups, badges, icons, flyout panels
- **Dialog mode** — any screen can open as a modal dialog

---

## Requirements

| Dependency | Version |
|---|---|
| PHP | ^8.4 |
| Laravel | ^13.0 |
| Node.js | ^20 |
| Inertia Laravel | ^3.0 |
| Laravel Fortify | ^1.28 |
| Spatie Permissions | ^6.0 |
| Spatie Translatable | ^6.0 |

---

## Installation

Inside an existing Laravel 13 application:

```bash
composer require swarakaka/darejer
php artisan darejer:install
```

The install command publishes the config, assets, and Fortify views, runs the required migrations, and wires Darejer's frontend into your host app.

After installing, build the frontend:

```bash
npm install
npm run dev
```

See the [installation guide](docs/getting-started/installation.md) for the full Vite config and entry point snippets.

---

## Quick start

```php
use Darejer\Screen\Screen;
use Darejer\Screen\Section;
use Darejer\Components\TranslatableInput;
use Darejer\Components\SelectComponent;
use Darejer\Actions\SaveAction;
use Darejer\Actions\CancelAction;

public function create(): Response
{
    return Screen::make('New Product')
        ->sections([
            Section::make('general')
                ->title(__darejer('General'))
                ->components(['name', 'category', 'status']),
        ])
        ->components([
            TranslatableInput::make('name')
                ->label('Product Name')
                ->required(),

            SelectComponent::make('category')
                ->label('Category')
                ->options(['furniture' => 'Furniture', 'electronics' => 'Electronics']),
        ])
        ->actions([
            SaveAction::make()->url(route('products.store')),
            CancelAction::make()->url(route('products.index')),
        ])
        ->render();
}
```

### Layout — sections and tabs

Screens organize fields with two fluent layout primitives. Both accept arrays of class instances — never raw arrays.

**`Section::make($key)`** — vertical, FastTab-style accordion groups. Sections can be collapsible, start collapsed, and toggle visibility via `dependOn()`.

```php
use Darejer\Screen\Section;

Screen::make(__darejer('Edit User'))
    ->sections([
        Section::make('identity')
            ->title(__darejer('Identity'))
            ->components(['username', 'email']),

        Section::make('password')
            ->title(__darejer('Password'))
            ->collapsible()
            ->collapsed()
            ->components(['password', 'password_confirmation']),

        Section::make('access')
            ->title(__darejer('Access'))
            ->dependOn(['field' => 'role', 'operator' => 'in', 'value' => ['admin', 'manager']])
            ->components(['role_ids', 'permission_ids']),
    ])
    ->components([/* ... */])
    ->render();
```

**`Tab::make($title)`** — horizontal tab bar above the form body, with each tab showing its own components. Pass `->name('stable-id')` if you need the active tab to survive a locale switch (otherwise a stable id is auto-derived from the components list).

```php
use Darejer\Screen\Tab;

Screen::make(__darejer('Edit Product'))
    ->tabs([
        Tab::make(__darejer('General'))
            ->name('general')
            ->components(['name', 'slug', 'category_id']),

        Tab::make(__darejer('Pricing'))
            ->name('pricing')
            ->components(['price', 'cost', 'tax_rate']),

        Tab::make(__darejer('SEO'))
            ->name('seo')
            ->dependOn(['field' => 'is_published', 'operator' => '=', 'value' => true])
            ->components(['meta_title', 'meta_description']),
    ])
    ->components([/* ... */])
    ->render();
```

`sections()` and `tabs()` are independent — pick whichever fits the screen. The component definitions in `->components([...])` are referenced from sections/tabs by their `name`.

---

## Documentation

Full documentation lives in the [`docs/`](docs) directory and on [github.com/swarakaka/darejer](https://github.com/swarakaka/darejer).

---

## Local development

Darejer is consumed as a Composer package, so the recommended workflow is to develop the package against a small Laravel app on your own machine — set up whatever playground you prefer.

### 1. Clone the package

```bash
git clone https://github.com/swarakaka/darejer.git
cd darejer
composer install
npm install
```

### 2. Create a local Laravel playground

Spin up a fresh Laravel app anywhere on your machine — this is your private sandbox, not part of the package:

```bash
composer create-project laravel/laravel my-playground
cd my-playground
```

### 3. Link the package via Composer path repository

Edit the playground's `composer.json` and point it at your local clone of Darejer:

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "../darejer",
      "options": { "symlink": true }
    }
  ],
  "minimum-stability": "dev",
  "prefer-stable": true
}
```

Then require the package and run the installer:

```bash
composer require swarakaka/darejer:@dev
php artisan key:generate
php artisan darejer:install
php artisan migrate --seed
```

### 4. Run it

```bash
npm install
npm run dev
php artisan serve
```

With `symlink: true`, any change you make inside the `darejer/` package is immediately reflected in your playground — no re-install needed. Frontend source under `darejer/resources/js` and `darejer/resources/css` is consumed live through the package's Vite plugin (`darejer/vite`), which aliases `@darejer` to the package's `resources/js`. Edits hot-reload via `npm run dev` — no publish or rebuild step is required.

---

## Contributing

Issues and pull requests are welcome at [github.com/swarakaka/darejer](https://github.com/swarakaka/darejer).

---

## License

MIT — see the [LICENSE](LICENSE) file.
