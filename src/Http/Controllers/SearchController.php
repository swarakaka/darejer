<?php

declare(strict_types=1);

namespace Darejer\Http\Controllers;

use Darejer\Support\GlobalSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Backs the topbar quick-jump. Walks every model registered with
 * `ModelRegistry` that uses the `Searchable` trait and folds matches
 * into a flat list of dropdown items grouped by model type.
 *
 * Route registered in `routes/darejer.php` under the standard
 * `darejer.` name prefix as `darejer.search`.
 */
class SearchController extends DarejerController
{
    protected bool $autoRoute = false;

    public function index(Request $request): JsonResponse
    {
        $key = 'darejer-search-'.($request->user()?->id ?? $request->ip());
        if (RateLimiter::tooManyAttempts($key, 60)) {
            return response()->json(['error' => 'Too many requests.'], 429);
        }
        RateLimiter::hit($key, 60);

        $term = (string) $request->get('q', '');
        $perModel = max(1, min((int) $request->get('per_model', 5), 20));

        return response()->json(GlobalSearch::search($term, $perModel));
    }
}
