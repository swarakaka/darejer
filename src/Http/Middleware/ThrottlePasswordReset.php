<?php

declare(strict_types=1);

namespace Darejer\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * IP-keyed throttle for Fortify's password-reset request endpoint.
 *
 * Fortify doesn't expose a `password-reset` slot in its `limiters` config
 * (only login + two-factor), so we wrap the route ourselves. Activates only
 * on `password.email` POST so it's safe to register globally on the web
 * group. The token-cooldown in `config/auth.php` still applies per email;
 * this limits how many emails one IP can spray.
 */
final class ThrottlePasswordReset
{
    public function __construct(private readonly RateLimiter $limiter) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('POST') || ! $request->routeIs('password.email')) {
            return $next($request);
        }

        $key = 'password-reset:'.$request->ip();
        $maxAttempts = 5;
        $decaySeconds = 3600;

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            throw new ThrottleRequestsException(
                'Too many password reset attempts. Try again later.',
                null,
                ['Retry-After' => (string) $this->limiter->availableIn($key)]
            );
        }

        $this->limiter->hit($key, $decaySeconds);

        return $next($request);
    }
}
