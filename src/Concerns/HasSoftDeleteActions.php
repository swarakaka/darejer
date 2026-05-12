<?php

declare(strict_types=1);

namespace Darejer\Concerns;

use Darejer\Routing\Route;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Restore / permanently-delete / bulk-action surface for soft-deletable
 * resources. Mix into a controller alongside the standard REST methods:
 *
 * ```
 * class FooController extends DarejerController
 * {
 *     use HasSoftDeleteActions;
 *
 *     protected function softDeleteModel(): string
 *     {
 *         return Foo::class;
 *     }
 *
 *     protected function softDeleteAbility(string $verb): string
 *     {
 *         // 'delete' | 'restore' | 'force_delete'
 *         return "module.foo.{$verb}";
 *     }
 * }
 * ```
 *
 * Routes registered on the controller (via attribute discovery):
 *   PATCH  {resource}/{id}/restore   → restore
 *   DELETE {resource}/{id}/force     → forceDestroy
 *   DELETE {resource}/bulk-destroy   → bulkDestroy
 *   PATCH  {resource}/bulk-restore   → bulkRestore
 *   DELETE {resource}/bulk-force     → bulkForceDestroy
 *
 * The `{id}` parameter is intentionally generic so it doesn't collide with
 * the controller's `$parameter` (which drives implicit-binding for
 * show/edit/update/destroy on the integer-id sibling routes).
 */
trait HasSoftDeleteActions
{
    abstract protected function softDeleteModel(): string;

    abstract protected function softDeleteAbility(string $verb): string;

    #[Route('PATCH', '{id}/restore', name: 'restore')]
    public function restore(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $this->authorizeSoftDelete('restore');

        $model = $this->softDeleteModel();
        $record = $model::onlyTrashed()->findOrFail($id);
        $record->restore();

        return $this->softDeleteSuccess($request, __('Record restored.'), ['id' => $id]);
    }

    #[Route('DELETE', '{id}/force', name: 'force_destroy')]
    public function forceDestroy(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $this->authorizeSoftDelete('force_delete');

        $model = $this->softDeleteModel();
        $record = $model::withTrashed()->findOrFail($id);

        try {
            $record->forceDelete();
        } catch (QueryException $e) {
            return $this->forceDeleteFkError($request, 1, $e);
        }

        return $this->softDeleteSuccess($request, __('Record permanently deleted.'), ['id' => $id]);
    }

    #[Route('DELETE', 'bulk-destroy', name: 'bulk_destroy')]
    public function bulkDestroy(Request $request): JsonResponse|RedirectResponse
    {
        $this->authorizeSoftDelete('delete');
        $ids = $this->validateBulkIds($request);

        $model = $this->softDeleteModel();
        $count = $model::query()->whereIn('id', $ids)->delete();

        return $this->softDeleteSuccess(
            $request,
            __(':count records deleted.', ['count' => $count]),
            ['count' => $count],
        );
    }

    #[Route('PATCH', 'bulk-restore', name: 'bulk_restore')]
    public function bulkRestore(Request $request): JsonResponse|RedirectResponse
    {
        $this->authorizeSoftDelete('restore');
        $ids = $this->validateBulkIds($request);

        $model = $this->softDeleteModel();
        $count = $model::onlyTrashed()->whereIn('id', $ids)->restore();

        return $this->softDeleteSuccess(
            $request,
            __(':count records restored.', ['count' => $count]),
            ['count' => $count],
        );
    }

    #[Route('DELETE', 'bulk-force', name: 'bulk_force_destroy')]
    public function bulkForceDestroy(Request $request): JsonResponse|RedirectResponse
    {
        $this->authorizeSoftDelete('force_delete');
        $ids = $this->validateBulkIds($request);

        $model = $this->softDeleteModel();
        $records = $model::withTrashed()->whereIn('id', $ids)->get();

        $deleted = 0;
        $failed = 0;
        $lastException = null;
        foreach ($records as $record) {
            try {
                $record->forceDelete();
                $deleted++;
            } catch (QueryException $e) {
                $failed++;
                $lastException = $e;
            }
        }

        if ($failed > 0 && $deleted === 0) {
            return $this->forceDeleteFkError($request, $failed, $lastException);
        }

        $message = $failed === 0
            ? __(':count records permanently deleted.', ['count' => $deleted])
            : __(
                ':deleted records permanently deleted, :failed could not be deleted (related records exist).',
                ['deleted' => $deleted, 'failed' => $failed],
            );

        return $this->softDeleteSuccess($request, $message, ['deleted' => $deleted, 'failed' => $failed]);
    }

    /**
     * @return array<int, int>
     */
    protected function validateBulkIds(Request $request): array
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        return array_values(array_unique(array_map('intval', $data['ids'])));
    }

    protected function authorizeSoftDelete(string $verb): void
    {
        $ability = $this->softDeleteAbility($verb);
        $user = auth()->user();

        if ($user && method_exists($user, 'hasRole') && $user->hasRole('super-admin')) {
            return;
        }

        if (! Gate::allows($ability)) {
            abort(403);
        }
    }

    protected function forceDeleteFkError(Request $request, int $failedCount, ?QueryException $exception): JsonResponse|RedirectResponse
    {
        $message = __('Cannot permanently delete: related records exist.');

        // Surface the underlying SQLSTATE only in non-production for
        // debugging; production users see a generic message.
        $detail = app()->environment('production') || $exception === null
            ? null
            : $exception->getMessage();

        if ($request->header('X-Inertia')) {
            Inertia::flash('error', $message);

            return back();
        }

        return $this->jsonError(
            $message,
            422,
            $detail ? ['detail' => [$detail]] : [],
        );
    }

    /**
     * Inertia requests need a redirect (so Inertia can re-fetch the page);
     * non-Inertia callers (tests, API consumers) get the standard JSON
     * envelope. Flash messages are surfaced via the shared layout toast.
     */
    protected function softDeleteSuccess(Request $request, string $message, array $data = []): JsonResponse|RedirectResponse
    {
        if ($request->header('X-Inertia')) {
            Inertia::flash('success', $message);

            return back();
        }

        return $this->jsonSuccess($message, $data);
    }
}
