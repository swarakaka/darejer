<?php

namespace Darejer;

use Darejer\Console\Commands\InstallCommand;
use Darejer\Console\Commands\LanguageCommand;
use Darejer\Console\Commands\LanguageExportCommand;
use Darejer\Data\ModelRegistry;
use Darejer\Http\Middleware\HandleInertiaRequests;
use Darejer\Routing\ControllerRouteRegistrar;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Translation\FileLoader;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Spatie\Permission\Models\Role as SpatieRole;

class DarejerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/darejer.php',
            'darejer'
        );

        // Apply Darejer-friendly Fortify defaults unless the host has
        // published their own `config/fortify.php`. Runs after Fortify's own
        // `mergeConfigFrom` (our provider registers second via discovery),
        // so our values win — without stomping on a host that has opted in
        // to full Fortify control by publishing the config.
        if (! file_exists(config_path('fortify.php'))) {
            $defaults = require __DIR__.'/../config/fortify.php';
            foreach ($defaults as $key => $value) {
                config()->set("fortify.{$key}", $value);
            }
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                LanguageCommand::class,
                LanguageExportCommand::class,
            ]);
        }
    }

    public function boot(): void
    {
        // Grant super-admin all abilities (Spatie laravel-permission convention).
        Gate::before(fn ($user, $ability) => method_exists($user, 'hasRole') && $user->hasRole('super-admin') ? true : null);

        // Publish config
        $this->publishes([
            __DIR__.'/../config/darejer.php' => config_path('darejer.php'),
        ], 'darejer-config');

        // Publish Fortify config override (Darejer defaults)
        $this->publishes([
            __DIR__.'/../config/fortify.php' => config_path('fortify.php'),
        ], 'darejer-fortify-config');

        // Publish migrations
        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'darejer-migrations');

        // Publish compiled assets (JS + CSS build output) to host app's public folder.
        $this->publishes([
            __DIR__.'/../public/build' => public_path('vendor/darejer'),
        ], 'darejer-assets');

        $this->loadRoutesFrom(__DIR__.'/../routes/darejer.php');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'darejer');

        // Also register the package's lang folder against the default
        // (un-namespaced) loader paths so Laravel's validator — which calls
        // `trans('validation.*')` without a namespace — picks up the
        // package-shipped messages for locales the host hasn't translated
        // (e.g. ckb). Inserted before the host's `lang_path` so the
        // host's own `lang/{locale}/*.php` files still win on conflict
        // (FileLoader merges later paths over earlier ones).
        $this->loadJsonTranslationsFrom(__DIR__.'/../lang');
        $this->callAfterResolving('translation.loader', function ($loader) {
            if (! $loader instanceof FileLoader) {
                return;
            }

            $packageLang = __DIR__.'/../lang';
            $reflection = new \ReflectionProperty($loader, 'paths');
            $paths = $reflection->getValue($loader);

            if (in_array($packageLang, $paths, true)) {
                return;
            }

            $hostIndex = array_search(lang_path(), $paths, true);

            if ($hostIndex === false) {
                $loader->addPath($packageLang);
            } else {
                array_splice($paths, $hostIndex, 0, [$packageLang]);
                $reflection->setValue($loader, $paths);
            }
        });

        // Zero-config: auto-load shipped migrations (alerts, etc.) so host
        // apps just `php artisan migrate` — no `vendor:publish` needed.
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Broadcast channels for package features (per-user alert websocket
        // channel). Wired after the framework boots so Laravel's
        // BroadcastServiceProvider has registered the BroadcastManager —
        // host apps don't need to touch their own `routes/channels.php`.
        $this->app->booted(function () {
            if (file_exists($channels = __DIR__.'/../routes/channels.php')) {
                require $channels;
            }
        });

        // Register Spatie's Role/Permission models against the data slug
        // registry so the package's Admin screens (and any host code) can
        // fetch them via `/darejer/data/role` & `/darejer/data/permission`
        // through Combobox without needing them under `App\Models`.
        ModelRegistry::register([
            'role' => SpatieRole::class,
            'permission' => SpatiePermission::class,
        ]);

        // Discover controllers extending `Darejer\Http\Controllers\DarejerController`
        // and auto-register their routes. Honours Laravel's route cache.
        if (! $this->app->routesAreCached()) {
            $mapping = config('darejer.controllers', [
                app_path('Http/Controllers') => 'App\\Http\\Controllers',
            ]);

            ControllerRouteRegistrar::discover($mapping)->register();

            // The package ships its own Admin controllers (Users, Roles,
            // Permissions) and Governance controllers (Audit Log). Discover
            // them after the host so host-defined routes win on conflict.
            ControllerRouteRegistrar::discover([
                __DIR__.'/Http/Controllers/Admin' => 'Darejer\\Http\\Controllers\\Admin',
                __DIR__.'/Http/Controllers/Governance' => 'Darejer\\Http\\Controllers\\Governance',
            ])->register();
        }

        // @darejerAssets Blade directive — emits <link> + <script> tags from
        // the published Vite manifest.
        Blade::directive('darejerAssets', function () {
            return "<?php echo \Darejer\Helpers\AssetHelper::tags(); ?>";
        });

        // Append Darejer's Inertia middleware to the `web` group. It replaces
        // both the host's own HandleInertiaRequests and any locale middleware:
        // it owns locale resolution, RTL direction, Inertia shared props, and
        // can be extended by the host via the Darejer config or a subclass.
        $this->app->booted(function () {
            $kernel = $this->app->make(Kernel::class);
            if (method_exists($kernel, 'appendMiddlewareToGroup')) {
                $kernel->appendMiddlewareToGroup('web', HandleInertiaRequests::class);
            }
        });
    }
}
