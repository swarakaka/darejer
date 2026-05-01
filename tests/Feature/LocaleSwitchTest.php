<?php

declare(strict_types=1);

use Darejer\Http\Controllers\LocaleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    config()->set('darejer.languages', ['en', 'ar', 'ckb']);
    config()->set('darejer.default_language', 'en');
});

function localeRequest(array $body, ?string $referer = null): Request
{
    $request = Request::create('/darejer/locale', 'POST', $body);

    if ($referer !== null) {
        $request->headers->set('referer', $referer);
    }

    // `back()` reads the application's bound Request, not the one passed
    // to the controller — bind ours so the redirect target matches the
    // referer we set.
    app()->instance('request', $request);

    return $request;
}

it('persists the chosen locale in the session and redirects back to the referer with its query string', function (): void {
    $referer = 'http://localhost/darejer/accounting/chart-of-accounts?account_type=asset&allow_posting=1&order=asc&page=1&sort=code&status=active';

    $response = (new LocaleController)->update(localeRequest(['locale' => 'ckb'], $referer));

    expect($response->getStatusCode())->toBe(302);
    expect($response->getTargetUrl())->toBe($referer);
    expect(session('darejer_locale'))->toBe('ckb');
});

it('rejects a locale that is not in the configured languages list', function (): void {
    expect(fn () => (new LocaleController)->update(localeRequest(['locale' => 'fr'])))
        ->toThrow(ValidationException::class);

    expect(session('darejer_locale'))->toBeNull();
});

it('rejects a missing locale field', function (): void {
    expect(fn () => (new LocaleController)->update(localeRequest([])))
        ->toThrow(ValidationException::class);
});

it('registers the locale.update route without requiring auth so guests can switch language', function (): void {
    $route = Route::getRoutes()->getByName('darejer.locale.update');

    expect($route)->not->toBeNull();
    expect($route->methods())->toContain('POST');
    expect($route->uri())->toBe('darejer/locale');
    expect($route->gatherMiddleware())
        ->toContain('web')
        ->not->toContain('auth');
});
