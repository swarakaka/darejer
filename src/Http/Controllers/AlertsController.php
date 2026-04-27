<?php

declare(strict_types=1);

namespace Darejer\Http\Controllers;

use Darejer\Models\Alert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Per-user notification feed. Pure JSON — the topbar Bell + slideover
 * fetch through these endpoints (initial unread count + paginated list)
 * and Reverb pushes the live updates over the private user channel.
 *
 * Routes registered in `routes/darejer.php`.
 */
class AlertsController extends DarejerController
{
    protected bool $autoRoute = false;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $perPage = min((int) $request->get('per_page', 20), 100);
        $unreadOnly = $request->boolean('unread');

        $query = Alert::query()
            ->forUser((int) $user->getAuthIdentifier())
            ->latest('id');

        if ($unreadOnly) {
            $query->unread();
        }

        $paginated = $query->paginate($perPage);

        return response()->json([
            'data' => collect($paginated->items())->map(fn (Alert $a) => $a->toFrontend())->all(),
            'meta' => [
                'total' => $paginated->total(),
                'unread_count' => Alert::query()
                    ->forUser((int) $user->getAuthIdentifier())
                    ->unread()
                    ->count(),
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
            ],
        ]);
    }

    public function count(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'unread_count' => Alert::query()
                ->forUser((int) $user->getAuthIdentifier())
                ->unread()
                ->count(),
        ]);
    }

    public function markRead(Request $request, int $id): JsonResponse
    {
        $alert = $this->findOwned($request, $id);
        $alert->markAsRead();

        return $this->jsonSuccess('OK', $alert->fresh()?->toFrontend());
    }

    public function markAllRead(Request $request): JsonResponse
    {
        Alert::query()
            ->forUser((int) $request->user()->getAuthIdentifier())
            ->unread()
            ->update(['read_at' => now()]);

        return $this->jsonSuccess('OK');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->findOwned($request, $id)->delete();

        return $this->jsonSuccess('OK');
    }

    public function clear(Request $request): JsonResponse
    {
        Alert::query()
            ->forUser((int) $request->user()->getAuthIdentifier())
            ->delete();

        return $this->jsonSuccess('OK');
    }

    private function findOwned(Request $request, int $id): Alert
    {
        $alert = Alert::query()
            ->forUser((int) $request->user()->getAuthIdentifier())
            ->find($id);

        if ($alert === null) {
            throw new NotFoundHttpException('Alert not found.');
        }

        return $alert;
    }
}
