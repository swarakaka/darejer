<?php

namespace Darejer\Routing;

use Attribute;

/**
 * Declares a custom route on a controller method.
 *
 * Used by `Darejer\Routing\ControllerRouteRegistrar` to auto-register
 * non-REST endpoints (bulk actions, status toggles, dialog screens, etc.)
 * without touching `routes/web.php`.
 *
 * Example:
 * ```php
 * use Darejer\Routing\Route;
 *
 * class ProductController extends \Darejer\Http\Controllers\DarejerController
 * {
 *     protected ?string $resource = 'products';
 *
 *     #[Route('POST', 'bulk/archive', name: 'bulk.archive')]
 *     public function bulkArchive(Request $request) { ... }
 * }
 * ```
 *
 * The `name` is appended to the controller's `$routeName` / `$resource`
 * prefix, so the fully-qualified route name above is `products.bulk.archive`.
 * The `uri` is appended to the controller's resource slug.
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Route
{
    /** @param string|array<int, string> $method HTTP verb(s) */
    public function __construct(
        public string|array $method,
        public string $uri,
        public ?string $name = null,
        public array $middleware = [],
        public bool $absolute = false,
    ) {}
}
