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
- **Permission system** — Spatie Permissions with super-admin bypass and canSee() on every component
- **DataTable** — server-side pagination, sorting, filtering from a single PHP class
- **Form system** — Inertia v3 useForm, validation errors, file uploads, dirty state
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

```bash
composer require swarakaka/darejer
php artisan darejer:install
php artisan vendor:publish --tag=darejer-assets
```

---

## Quick start

```php
use Darejer\Screen\Screen;
use Darejer\Components\TranslatableInput;
use Darejer\Components\SelectComponent;
use Darejer\Actions\SaveAction;
use Darejer\Actions\CancelAction;

public function create(): Response
{
    return Screen::make('New Product')
        ->sections([
            ['title' => 'General', 'components' => ['name', 'category', 'status']],
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

---

## Documentation

Full documentation: [github.com/swarakaka/darejer](https://github.com/swarakaka/darejer)

---

## Local Development

```bash
git clone https://github.com/swarakaka/darejer.git
git clone https://github.com/swarakaka/darejer-playground.git

cd darejer-playground
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

---

## License

MIT — see [LICENSE](LICENSE) file.
# darejer
