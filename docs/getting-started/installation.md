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

## Publish assets

The compiled JS + CSS bundle ships pre-built. Publish it to your host app's `public/vendor/darejer`:

```bash
php artisan vendor:publish --tag=darejer-assets --force
```

> Always publish with `--force` after upgrading the package — the build output is the source of truth and stale assets cause silent UI regressions.

Optionally publish:

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

## Render assets in your layout

In your root Blade layout (`resources/views/app.blade.php` or wherever your Inertia root lives) emit the package's bundle tags:

```blade
<!DOCTYPE html>
<html>
<head>
   @routes
   @darejerAssets
   <x-inertia::head />
</head>
<body>
     <x-inertia::app />
</body>
</html>
```

The `@darejerAssets` directive expands to the `<link>` + `<script>` tags driven by the published Vite manifest at `public/vendor/darejer/manifest.json`.

## Verify

Visit `/darejer` (or whatever you set `darejer.route_prefix` to) — you should land on Darejer's bundled dashboard, served through Fortify's auth flow.

If you see "401 Unauthorized" you haven't logged in yet. Visit `/darejer/login`.

## Next

- [Configure languages, prefix, middleware](configuration.md)
- [Build your first screen](first-screen.md)
