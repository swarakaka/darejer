<?php

namespace Darejer\Http\Middleware;

use Darejer\Navigation\NavigationManager;
use Darejer\Support\Locales;
use Illuminate\Http\Request;
use Inertia\Middleware;

/**
 * Darejer's zero-config Inertia middleware.
 *
 * Replaces the standard `App\Http\Middleware\HandleInertiaRequests` shipped
 * by Inertia. It owns every shared prop the Darejer frontend needs:
 *  - `darejer`: configured languages, default, per-locale text direction,
 *    current locale + direction + is_rtl flag
 *  - `flash`:   Laravel session flash bag
 *  - `auth`:    current user with permissions / roles / super-admin flag
 *  - `navigation` + `breadcrumbs`: resolved via NavigationManager + route
 *
 * Locale handling is built in: reads `?lang=` (persisting to session),
 * falls back to `session('darejer_locale')`, then the config default, and
 * validates against the configured languages. Applies `app()->setLocale()`
 * so Spatie Translatable + Laravel localization resolve against it for the
 * rest of the request cycle.
 *
 * Host apps don't need their own `HandleInertiaRequests` — this is
 * registered to the `web` middleware group from the service provider.
 */
class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function handle(Request $request, \Closure $next)
    {
        $this->applyLocale($request);

        return parent::handle($request, $next);
    }

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $languages = config('darejer.languages', ['en']);
        $default = config('darejer.default_language', 'en');
        $current = app()->getLocale();

        $directions = collect($languages)
            ->mapWithKeys(fn ($l) => [$l => Locales::direction($l)])
            ->all();

        return array_merge(parent::share($request), [
            'darejer' => fn () => [
                'languages' => $languages,
                'default_language' => $default,
                'locale' => $current,
                'direction' => Locales::direction($current),
                'is_rtl' => Locales::isRtl($current),
                'directions' => $directions,
            ],
            'flash' => fn () => [
                'success' => session('success'),
                'error' => session('error'),
                'warning' => session('warning'),
                'info' => session('info'),
            ],
            'auth' => fn () => [
                'user' => auth()->check() ? $this->shareUser(auth()->user()) : null,
            ],
            'navigation' => fn () => NavigationManager::toArray(),
            'breadcrumbs' => fn () => [],
        ]);
    }

    protected function applyLocale(Request $request): void
    {
        $languages = config('darejer.languages', ['en']);
        $default = config('darejer.default_language', 'en');

        if ($request->has('lang') && in_array($request->get('lang'), $languages, true)) {
            $locale = $request->get('lang');
            session(['darejer_locale' => $locale]);
        } else {
            $locale = session('darejer_locale', $default);
        }

        if (! in_array($locale, $languages, true)) {
            $locale = $default;
        }

        app()->setLocale($locale);
    }

    protected function shareUser(mixed $user): array
    {
        return [
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'permissions' => method_exists($user, 'darejerPermissions')
                ? $user->darejerPermissions()
                : (method_exists($user, 'getAllPermissions')
                    ? $user->getAllPermissions()->pluck('name')->toArray()
                    : []),
            'roles' => method_exists($user, 'darejerRoles')
                ? $user->darejerRoles()
                : (method_exists($user, 'getRoleNames')
                    ? $user->getRoleNames()->toArray()
                    : []),
            'isSuperAdmin' => method_exists($user, 'isSuperAdmin')
                ? $user->isSuperAdmin()
                : false,
        ];
    }
}
