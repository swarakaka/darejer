<?php

namespace Darejer\Http\Middleware;

use Darejer\Navigation\NavigationManager;
use Darejer\Support\Locales;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Inertia\Middleware;

/**
 * Darejer's zero-config Inertia middleware.
 *
 * Replaces the standard `App\Http\Middleware\HandleInertiaRequests` shipped
 * by Inertia. It owns every shared prop the Darejer frontend needs:
 *  - `darejer`: configured languages, default, per-locale text direction,
 *    current locale + direction + is_rtl flag
 *  - `auth`:    current user with permissions / roles / super-admin flag
 *  - `navigation` + `breadcrumbs`: resolved via NavigationManager + route
 *
 * Flash messages are NOT shared here — Inertia v3 emits flash data at the
 * top-level page key (`page.flash`) via its own `resolveFlashData()`, so
 * controllers should call `Inertia::flash('success', '...')` and the
 * frontend should read `usePage().flash`. See the FlashMessage component.
 *
 * Locale handling is built in: reads `?lang=` (persisting to session +
 * a long-lived cookie), falls back to `session('darejer_locale')`, then
 * the `darejer_locale` cookie (so the choice survives logout, which wipes
 * the session), then the config default. Validates against the configured
 * languages. Applies `app()->setLocale()` so Spatie Translatable + Laravel
 * localization resolve against it for the rest of the request cycle.
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
                'app_name' => config('darejer.app_name', 'Darejer'),
                'languages' => $languages,
                'default_language' => $default,
                'locale' => $current,
                'direction' => Locales::direction($current),
                'is_rtl' => Locales::isRtl($current),
                'directions' => $directions,
                'translations' => $this->hostTranslations($current),
            ],
            'auth' => fn () => [
                'user' => auth()->check() ? $this->shareUser(auth()->user()) : null,
            ],
            'navigation' => fn () => NavigationManager::toArray(),
            'breadcrumbs' => fn () => [],
        ]);
    }

    /**
     * Load the host app's `lang/<locale>.json` so the frontend `__()` helper
     * can resolve labels that PHP `__()` already finds server-side. Without
     * this, dynamic strings built in JS (e.g. table column headers derived
     * from snake_case row keys) miss the host's translation file entirely.
     *
     * @return array<string,string>
     */
    protected function hostTranslations(string $locale): array
    {
        $path = lang_path("{$locale}.json");

        if (! is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : [];
    }

    protected function applyLocale(Request $request): void
    {
        $languages = config('darejer.languages', ['en']);
        $default = config('darejer.default_language', 'en');

        if ($request->has('lang') && in_array($request->get('lang'), $languages, true)) {
            $locale = $request->get('lang');
            session(['darejer_locale' => $locale]);
            Cookie::queue('darejer_locale', $locale, 60 * 24 * 365);
        } elseif ($sessionLocale = session('darejer_locale')) {
            $locale = $sessionLocale;
        } elseif (($cookieLocale = $request->cookie('darejer_locale')) && in_array($cookieLocale, $languages, true)) {
            $locale = $cookieLocale;
            session(['darejer_locale' => $locale]);
        } else {
            $locale = $default;
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
