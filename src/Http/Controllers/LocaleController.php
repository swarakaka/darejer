<?php

declare(strict_types=1);

namespace Darejer\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Persists the user's chosen locale in the session, then redirects back
 * so the current URL (and its query string) is preserved across the
 * switch. The shared `HandleInertiaRequests::applyLocale` middleware
 * picks up `session('darejer_locale')` on the next request.
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

        return back();
    }
}
