<?php

namespace Darejer\Routing;

use Illuminate\Support\Facades\Route as RouteFacade;
use RuntimeException;

/**
 * Builds URL patterns for Darejer DataTable / TreeGrid row actions.
 *
 * Why this exists
 * ───────────────
 * The DataTable / TreeGrid frontend substitutes `{id}` placeholders in
 * row-action URLs with each row's primary-key value at render time. The
 * documented API was therefore:
 *
 *     RowAction::edit(route('products.edit', '{id}'))
 *
 * Laravel 13.6+ tightened its URL generator — passing a string of the
 * form `'{xxx}'` is now interpreted as a parameter-name lookup ("Missing
 * parameter: xxx") instead of a positional placeholder, breaking the
 * convention.
 *
 * This helper resolves a registered route's URI template and substitutes
 * its first parameter with `{id}` (or any other placeholder), without
 * going through the URL generator and without hardcoding paths in host
 * apps.
 *
 *     RowAction::edit(RoutePattern::row('products.edit'))
 *       → "/darejer/products/{id}/edit"
 *
 *     RowAction::view(RoutePattern::row('orders.line.show', '{lineId}'))
 *       → "/orders/{id}/lines/{lineId}"   (after also calling row twice)
 */
class RoutePattern
{
    /**
     * Resolve a route name and return a URL template with the FIRST
     * `{param}` substituted with `{id}` (or a custom placeholder).
     *
     * Multi-parameter routes can chain calls or post-process the
     * returned string with str_replace.
     *
     * @throws RuntimeException when the route is not registered.
     */
    public static function row(string $routeName, string $placeholder = '{id}'): string
    {
        $route = RouteFacade::getRoutes()->getByName($routeName);

        if (! $route) {
            throw new RuntimeException("Route [{$routeName}] is not registered.");
        }

        $uri = '/'.ltrim($route->uri(), '/');

        return preg_replace('/\{[^}]+\}/', $placeholder, $uri, 1);
    }

    /**
     * Build a URL template for a route that has multiple parameters.
     * Pass an associative array mapping each route parameter name to
     * the placeholder string the frontend should substitute.
     *
     *     RoutePattern::for('invoices.lines.edit', [
     *         'invoice' => '{invoiceId}',
     *         'line'    => '{lineId}',
     *     ])
     *     → "/invoices/{invoiceId}/lines/{lineId}/edit"
     */
    public static function for(string $routeName, array $placeholders): string
    {
        $route = RouteFacade::getRoutes()->getByName($routeName);

        if (! $route) {
            throw new RuntimeException("Route [{$routeName}] is not registered.");
        }

        $uri = '/'.ltrim($route->uri(), '/');

        return preg_replace_callback('/\{([^}?]+)\??\}/', function ($match) use ($placeholders, $routeName) {
            $param = $match[1];
            if (! array_key_exists($param, $placeholders)) {
                throw new RuntimeException(
                    "Missing placeholder for parameter [{$param}] of route [{$routeName}].",
                );
            }

            return $placeholders[$param];
        }, $uri);
    }
}
