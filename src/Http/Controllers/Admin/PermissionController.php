<?php

declare(strict_types=1);

namespace Darejer\Http\Controllers\Admin;

use Darejer\DataGrid\Column;
use Darejer\DataGrid\Filter;
use Darejer\DataTable\DataTable;
use Darejer\Http\Controllers\DarejerController;
use Illuminate\Http\Request;
use Inertia\Response;
use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * Admin → Permissions. Read-only viewer for the registered permission set.
 *
 * Permissions are seeded by the host (typically `PermissionSeeder`) — the
 * Darejer admin UI never creates or deletes them, since their identifiers
 * are baked into `canSee()` calls across PHP and Vue. This screen exists
 * only so admins can audit the catalog and see which roles each is granted
 * to.
 */
class PermissionController extends DarejerController
{
    protected ?string $resource = 'permissions';

    protected ?string $routeName = 'darejer.admin.permissions';

    protected ?string $routePrefix = 'darejer/admin';

    public function index(Request $request): Response
    {
        $this->authorizePermission('system.permission.viewAny');

        return DataTable::make(SpatiePermission::class)
            ->title(__darejer('Permissions'))
            ->breadcrumbs([
                ['label' => __darejer('Administration')],
                ['label' => __darejer('Permissions')],
            ])
            ->columns([
                Column::make('id')->label('#')->width('80px')->sortable(),
                Column::make('name')->label(__darejer('Name'))->sortable()->searchable(),
                Column::make('guard_name')->label(__darejer('Guard'))->sortable(),
                Column::make('roles_csv')->label(__darejer('Granted To'))
                    ->displayUsing(fn ($permission) => $permission->roles->pluck('name')->join(', ') ?: '—'),
                Column::make('created_at')->label(__darejer('Created'))->sortable()->dateTime(),
            ])
            ->filters([
                Filter::text('name')->label(__darejer('Name')),
                Filter::select('guard_name')->label(__darejer('Guard'))->options([
                    'web' => 'web',
                    'api' => 'api',
                ]),
            ])
            ->with(['roles:id,name'])
            ->selectable(false)
            ->defaultSort('name', 'asc')
            ->render($request);
    }

    protected function authorizePermission(string $permission): void
    {
        $user = auth()->user();
        if (! $user || ! ($user->can($permission) || (method_exists($user, 'hasRole') && $user->hasRole('super-admin')))) {
            abort(403);
        }
    }
}
