# Installation

Darejer is a Composer package: `swarakaka/darejer`.

## Requirements

| Dependency | Version |
|---|---|
| PHP | `^8.4` |
| Laravel | `^13.0` |
| Inertia Laravel | `^3.0` |
| Laravel Fortify | `^1.38` |
| Spatie Permission | `^6.0` |
| Spatie Translatable | `^6.0` |
| Tightenco Ziggy | `^2.0` |

## Install

```bash
composer require swarakaka/darejer
```

The package's service providers are auto-discovered:

- `Darejer\DarejerServiceProvider`
- `Darejer\Providers\FortifyServiceProvider`

## Optional config publishing

```bash
# Override darejer.php config (languages, prefix, middleware)
php artisan vendor:publish --tag=darejer-config

# Override fortify.php with Darejer's defaults (Inertia views wired)
php artisan vendor:publish --tag=darejer-fortify-config

# Migrations (alerts table — also auto-loaded if not published)
php artisan vendor:publish --tag=darejer-migrations
```

## Run migrations

```bash
php artisan migrate
```

Darejer ships its own migrations (e.g. the `alerts` table) and auto-loads them — `php artisan migrate` is enough; you do not need to publish them first.

## Wire up the frontend

Darejer's Vue/Inertia frontend is consumed by your host app's Vite build via the `darejer/vite` plugin — there is no asset publishing step.

### 1. Install npm dependencies

```bash
pnpm add -D vite laravel-vite-plugin @tailwindcss/vite tailwindcss
pnpm add darejer
```

> When working against a local checkout of the package, declare it as a file dep: `"darejer": "file:vendor/swarakaka/darejer"`.

`.npmrc` should hoist the package's transitive deps so the host's Vite can resolve them:

```ini
shamefully-hoist=true
```

### 2. `vite.config.js`

```js
import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import darejer from 'darejer/vite'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        darejer(),
        tailwindcss(),
    ],
})
```

### 3. `resources/js/app.js`

```js
import { bootstrapDarejer } from 'darejer/bootstrap'

// Optional: host-app Vue pages override package pages with the same Inertia name.
const hostPages = import.meta.glob('./pages/**/*.vue')

bootstrapDarejer({ hostPages })
```

### 4. `resources/css/app.css`

```css
@import 'darejer/css';
```

### 5. Root Blade layout

```blade
<!DOCTYPE html>
<html>
<head>
   @routes
   @vite(['resources/css/app.css', 'resources/js/app.js'])
   <x-inertia::head />
</head>
<body>
     <x-inertia::app />
</body>
</html>
```

## Verify

Visit `/darejer` (or whatever you set `darejer.route_prefix` to) — you should land on Darejer's bundled dashboard, served through Fortify's auth flow.

If you see "401 Unauthorized" you haven't logged in yet. Visit `/darejer/login`.

## Next

- [Configure languages, prefix, middleware](configuration.md)
- [Build your first screen](first-screen.md)
