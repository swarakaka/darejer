<?php

namespace Darejer\Routing;

use Darejer\Http\Controllers\DarejerController;
use Illuminate\Support\Facades\Route as RouteFacade;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\Finder\Finder;

/**
 * Discovers controllers that extend `Darejer\Http\Controllers\Controller`
 * and registers their routes automatically — REST verbs based on method
 * presence, custom endpoints based on `#[Darejer\Routing\Route]` attributes.
 *
 * Wired in `DarejerServiceProvider::boot()`. Honours Laravel's route cache:
 * when routes are cached, discovery is skipped.
 */
class ControllerRouteRegistrar
{
    /** @var array<int, string> Discovered controller FQCNs */
    protected array $controllers = [];

    /**
     * @param  array<string, string>  $mapping  Map of absolute directory path
     *                                          → PSR-4 namespace (e.g. `[app_path('Http/Controllers') => 'App\\Http\\Controllers']`)
     */
    public static function discover(array $mapping): static
    {
        $instance = new static;

        foreach ($mapping as $path => $namespace) {
            $instance->scan($path, rtrim($namespace, '\\'));
        }

        return $instance;
    }

    public function register(): void
    {
        foreach ($this->controllers as $class) {
            $this->registerController($class);
        }
    }

    protected function scan(string $basePath, string $baseNamespace): void
    {
        if (! is_dir($basePath)) {
            return;
        }

        foreach ((new Finder)->files()->in($basePath)->name('*.php') as $file) {
            $relative = str_replace(
                [rtrim($basePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, '.php'],
                ['', '\\', ''],
                $file->getRealPath(),
            );

            $class = $baseNamespace.'\\'.$relative;

            if (! class_exists($class)) {
                continue;
            }

            $reflection = new ReflectionClass($class);
            if ($reflection->isAbstract() || ! $reflection->isSubclassOf(DarejerController::class)) {
                continue;
            }

            $this->controllers[] = $class;
        }
    }

    protected function registerController(string $class): void
    {
        /** @var DarejerController $instance */
        $instance = app()->make($class);

        if (! $instance->darejerAutoRoute()) {
            return;
        }

        $middleware = $instance->darejerMiddleware();
        $prefix = $instance->darejerRoutePrefix();

        $group = RouteFacade::middleware($middleware);
        if ($prefix) {
            $group = $group->prefix($prefix);
        }

        $group->group(function () use ($class, $instance) {
            $this->registerResourceRoutes($class, $instance);
            $this->registerFormRoutes($class, $instance);
            $this->registerAttributeRoutes($class, $instance);
        });
    }

    /**
     * Register a single `GET /{resource}/forms/{form}` route per controller
     * that exposes any reusable Form definitions returned by `forms()`.
     * The route returns the form schema as JSON so frontend consumers
     * (e.g. Combobox's inline create dialog) can fetch via `useHttp`
     * without any Inertia navigation.
     *
     * `forms()` is invoked at REQUEST time, not boot time — building a
     * Form may instantiate `Combobox::model(...)`, which calls `route()`
     * for `darejer.data.index`. That route doesn't exist yet during boot
     * discovery, so eagerly calling forms() here would deadlock.
     */
    protected function registerFormRoutes(string $class, DarejerController $instance): void
    {
        $resource = $instance->darejerResource();
        $name = $instance->darejerRouteName();

        if (! $resource || ! $name) {
            return;
        }

        // Only register when the subclass actually overrides forms().
        $reflection = new ReflectionMethod($class, 'forms');
        if ($reflection->getDeclaringClass()->getName() === DarejerController::class) {
            return;
        }

        RouteFacade::get("{$resource}/forms/{form}", function (string $form) use ($class) {
            /** @var DarejerController $controller */
            $controller = app()->make($class);
            foreach ($controller->forms() as $f) {
                if ($f->getName() === $form) {
                    return response()->json($f->toArray());
                }
            }
            abort(404, "Form [{$form}] not defined on this controller.");
        })->name("{$name}.forms");
    }

    protected function registerResourceRoutes(string $class, DarejerController $instance): void
    {
        $resource = $instance->darejerResource();
        $name = $instance->darejerRouteName();

        if (! $resource || ! $name) {
            return;
        }

        $param = $instance->darejerParameter();
        $binding = "{{$param}}";
        $model = $instance->darejerModel();

        // Constrain resource-bound parameters to integers so word-shaped
        // sibling URIs ("/opportunities/kanban", "/leads/import") fall
        // through to the controller's `#[Route]` attribute routes instead
        // of being captured as the `{param}` of show/edit/update/destroy.
        // Without this, Laravel matches the first registered pattern and
        // a literal segment like "kanban" gets passed in as the model id.
        $intRegex = ['^[0-9]+$'];

        if (method_exists($class, 'index')) {
            RouteFacade::get($resource, [$class, 'index'])->name("{$name}.index");
        }
        if (method_exists($class, 'create')) {
            RouteFacade::get("{$resource}/create", [$class, 'create'])->name("{$name}.create");
        }
        if (method_exists($class, 'store')) {
            RouteFacade::post($resource, [$class, 'store'])->name("{$name}.store");
        }
        if (method_exists($class, 'show')) {
            $route = RouteFacade::get("{$resource}/{$binding}", [$class, 'show'])
                ->where($param, '[0-9]+')
                ->name("{$name}.show");
            $this->applyBinding($route, $param, $model);
        }
        if (method_exists($class, 'edit')) {
            $route = RouteFacade::get("{$resource}/{$binding}/edit", [$class, 'edit'])
                ->where($param, '[0-9]+')
                ->name("{$name}.edit");
            $this->applyBinding($route, $param, $model);
        }
        if (method_exists($class, 'update')) {
            $route = RouteFacade::match(['PUT', 'PATCH'], "{$resource}/{$binding}", [$class, 'update'])
                ->where($param, '[0-9]+')
                ->name("{$name}.update");
            $this->applyBinding($route, $param, $model);
        }
        if (method_exists($class, 'destroy')) {
            $route = RouteFacade::delete("{$resource}/{$binding}", [$class, 'destroy'])
                ->where($param, '[0-9]+')
                ->name("{$name}.destroy");
            $this->applyBinding($route, $param, $model);
        }
    }

    protected function registerAttributeRoutes(string $class, DarejerController $instance): void
    {
        $reflection = new ReflectionClass($class);
        $resource = $instance->darejerResource();
        $name = $instance->darejerRouteName();
        $model = $instance->darejerModel();
        $param = $instance->darejerParameter();

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isStatic() || $method->getDeclaringClass()->isAbstract()) {
                continue;
            }

            foreach ($method->getAttributes(Route::class) as $attribute) {
                /** @var Route $meta */
                $meta = $attribute->newInstance();

                $uri = $meta->absolute || ! $resource
                    ? ltrim($meta->uri, '/')
                    : "{$resource}/".ltrim($meta->uri, '/');

                $verbs = array_map('strtoupper', (array) $meta->method);

                $route = RouteFacade::match($verbs, $uri, [$class, $method->getName()]);

                if ($meta->name) {
                    $route->name($name && ! $meta->absolute ? "{$name}.{$meta->name}" : $meta->name);
                }
                if ($meta->middleware) {
                    $route->middleware($meta->middleware);
                }

                if ($model && str_contains($uri, "{{$param}}")) {
                    $this->applyBinding($route, $param, $model);
                }
            }
        }
    }

    protected function applyBinding($route, string $param, ?string $model): void
    {
        if ($model) {
            $route->setParameter($param, null);
            // Implicit binding resolves via the parameter name → model map
            // registered via Route::model(). The model binding is applied
            // globally below; see `registerModelBinding()`.
            $this->registerModelBinding($param, $model);
        }
    }

    protected function registerModelBinding(string $param, string $model): void
    {
        static $bound = [];
        $key = "{$param}:{$model}";
        if (isset($bound[$key])) {
            return;
        }
        $bound[$key] = true;

        RouteFacade::model($param, $model);
    }
}
