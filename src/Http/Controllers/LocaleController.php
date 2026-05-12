<?php

declare(strict_types=1);

namespace Darejer\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cookie;

/**
 * Persists the user's chosen locale in the session and a long-lived cookie,
 * then redirects back so the current URL (and its query string) is preserved
 * across the switch. The shared `HandleInertiaRequests::applyLocale`
 * middleware picks up `session('darejer_locale')` on the next request, and
 * falls back to the cookie after logout (which wipes the session).
 */
class LocaleController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $languages = config('darejer.languages', ['en']);

        $data = $request->validate([
            'locale' => ['required', 'string', 'in:'.implode(',', $languages)],
        ]);

        session(['darejer_locale' => $data['locale']]);
        Cookie::queue('darejer_locale', $data['locale'], 60 * 24 * 365);

        return back();
    }
}
