<?php

declare(strict_types=1);

namespace Darejer\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sets HTTP defence-in-depth headers on every response.
 *
 * Production gates HSTS behind an HTTPS request so plain-HTTP local dev
 * (Herd / `php artisan serve`) still works. CSP allows Vite's HMR client
 * in non-production (it injects inline scripts and uses `eval`), and
 * tightens to no-`unsafe-eval` in production builds.
 */
final class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $headers = $response->headers;

        if (! $headers->has('X-Frame-Options')) {
            $headers->set('X-Frame-Options', 'SAMEORIGIN');
        }

        if (! $headers->has('X-Content-Type-Options')) {
            $headers->set('X-Content-Type-Options', 'nosniff');
        }

        if (! $headers->has('Referrer-Policy')) {
            $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        }

        if (! $headers->has('Permissions-Policy')) {
            $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), interest-cohort=()');
        }

        if (! $headers->has('Content-Security-Policy')) {
            $headers->set('Content-Security-Policy', $this->csp(app()->environment('production')));
        }

        if (app()->environment('production') && $request->isSecure() && ! $headers->has('Strict-Transport-Security')) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    private function csp(bool $production): string
    {
        $scriptSrc = $production
            ? "'self' 'unsafe-inline'"
            : "'self' 'unsafe-inline' 'unsafe-eval'";

        return implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
            "img-src 'self' data: blob:",
            "font-src 'self' data:",
            "style-src 'self' 'unsafe-inline'",
            "script-src {$scriptSrc}",
            "connect-src 'self' ws: wss:",
            "object-src 'none'",
        ]);
    }
}
