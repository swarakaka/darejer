<?php

namespace Darejer\Data;

use Illuminate\Support\Str;

/**
 * Maps URL slugs (e.g. "product") to Eloquent model classes. Called from the
 * host app's service provider — eliminates the brittle App\Models guessing
 * approach for anything outside that namespace.
 */
class ModelRegistry
{
    /** @var array<string, class-string> */
    protected static array $registry = [];

    /**
     * Register one or more model classes. Pass a flat list to derive slugs
     * from class basenames, or an associative array for explicit slugs.
     *
     *   ModelRegistry::register([Product::class, Category::class]);
     *   ModelRegistry::register(['product' => Product::class]);
     */
    public static function register(array $models): void
    {
        foreach ($models as $key => $value) {
            if (is_int($key)) {
                $modelClass = $value;
                $slug = strtolower(Str::singular(class_basename($modelClass)));
            } else {
                $slug = strtolower($key);
                $modelClass = $value;
            }

            static::$registry[$slug] = $modelClass;
        }
    }

    /**
     * Resolve a URL slug to a model class. Falls back to the `models` config
     * map, then to App\Models\{Studly}.
     */
    public static function resolve(string $slug): ?string
    {
        if (isset(static::$registry[$slug])) {
            return static::$registry[$slug];
        }

        $configMap = config('darejer.models', []);
        if (isset($configMap[$slug])) {
            return $configMap[$slug];
        }

        $guess = 'App\\Models\\'.Str::studly(Str::singular($slug));
        if (class_exists($guess)) {
            return $guess;
        }

        return null;
    }

    public static function has(string $slug): bool
    {
        return static::resolve($slug) !== null;
    }

    public static function all(): array
    {
        return static::$registry;
    }

    public static function flush(): void
    {
        static::$registry = [];
    }
}
