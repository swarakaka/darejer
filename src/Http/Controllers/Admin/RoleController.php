<?php

declare(strict_types=1);

namespace Darejer\Http\Controllers\Admin;

use Darejer\Actions\DeleteAction;
use Darejer\Actions\LinkAction;
use Darejer\Components\Combobox;
use Darejer\Components\TextInput;
use Darejer\DataGrid\Column;
use Darejer\DataGrid\Filter;
use Darejer\DataGrid\RowAction;
use Darejer\DataTable\DataTable;
use Darejer\Forms\Form;
use Darejer\Http\Controllers\DarejerController;
use Darejer\Routing\RoutePattern;
use Darejer\Screen\Section;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Response;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\PermissionRegistrar;

/**
 * Admin → Roles. CRUD over Spatie roles with attached-permission editing.
 * The `super-admin` role is protected from edits and deletes — Spatie's
 * Gate::before in DarejerServiceProvider grants it everything implicitly,
 * so its permission list is intentionally empty.
 */
class RoleController extends DarejerController
{
    protected ?string $resource = 'roles';

    protected ?string $routeName = 'darejer.admin.roles';

    protected ?string $routePrefix = 'darejer/admin';

    public function index(Request $request): Response
    {
        $this->authorizePermission('system.role.viewAny');

        return DataTable::make(SpatieRole::class)
            ->title(__darejer('Roles'))
            ->breadcrumbs([
                ['label' => __darejer('Administration')],
                ['label' => __darejer('Roles')],
            ])
            ->columns([
                Column::make('id')->label('#')->width('80px')->sortable(),
                Column::make('name')->label(__darejer('Name'))->sortable()->searchable(),
                Column::make('guard_name')->label(__darejer('Guard'))->sortable(),
                Column::make('permissions_count')->label(__darejer('Permissions'))
                    ->displayUsing(fn ($role) => (string) $role->permissions->count()),
                Column::make('created_at')->label(__darejer('Created'))->sortable()->dateTime(),
            ])
            ->filters([
                Filter::text('name')->label(__darejer('Name')),
                Filter::select('guard_name')->label(__darejer('Guard'))->options([
                    'web' => 'web',
                    'api' => 'api',
                ]),
            ])
            ->headerActions([
                LinkAction::make(__darejer('New Role'))
                    ->url(route('darejer.admin.roles.create'))
                    ->icon('Plus')
                    ->variant('default')
                    ->canSee('system.role.update'),
            ])
            ->rowActions([
                RowAction::edit(RoutePattern::row('darejer.admin.roles.edit'))
                    ->canSee('system.role.update'),
                RowAction::delete(RoutePattern::row('darejer.admin.roles.destroy'))
                    ->canSee('system.role.update'),
            ])
            ->with(['permissions:id'])
            ->defaultSort('id', 'asc')
            ->render($request);
    }

    public function create(): Response
    {
        $this->authorizePermission('system.role.update');

        return $this->form()
            ->title(__darejer('New Role'))
            ->record([
                'guard_name' => $this->defaultGuard(),
                'permission_ids' => [],
            ])
            ->save(route('darejer.admin.roles.store'), 'POST')
            ->cancel(route('darejer.admin.roles.index'))
            ->renderAsScreen();
    }

    public function edit(int $role): Response
    {
        $this->authorizePermission('system.role.update');

        $record = SpatieRole::query()->with('permissions:id')->findOrFail($role);

        if ($record->name === 'super-admin') {
            abort(403, __darejer('The super-admin role cannot be edited.'));
        }

        return $this->form()
            ->title(__darejer('Edit Role').' — '.$record->name)
            ->record(array_merge($record->toArray(), [
                'permission_ids' => $record->permissions->pluck('id')->all(),
            ]))
            ->save(route('darejer.admin.roles.update', $record->id), 'PUT')
            ->cancel(route('darejer.admin.roles.index'))
            ->addActions([
                DeleteAction::make(__darejer('Delete'))
                    ->url(route('darejer.admin.roles.destroy', $record->id))
                    ->canSee(fn () => auth()->user()?->can('system.role.update')),
            ])
            ->renderAsScreen();
    }

    public function store(Request $request)
    {
        $this->authorizePermission('system.role.update');

        $data = $this->validateRequest($request);

        $role = SpatieRole::create([
            'name' => $data['name'],
            'guard_name' => $data['guard_name'] ?? $this->defaultGuard(),
        ]);

        $role->syncPermissions($this->resolvePermissionNames($data['permission_ids'] ?? []));

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('darejer.admin.roles.index')
            ->with('flash', ['type' => 'success', 'message' => __darejer('Role created.')]);
    }

    public function update(Request $request, int $role)
    {
        $this->authorizePermission('system.role.update');

        $record = SpatieRole::query()->findOrFail($role);

        if ($record->name === 'super-admin') {
            abort(403, __darejer('The super-admin role cannot be edited.'));
        }

        $data = $this->validateRequest($request, $record->id);

        $record->name = $data['name'];
        if (isset($data['guard_name'])) {
            $record->guard_name = $data['guard_name'];
        }
        $record->save();

        if (array_key_exists('permission_ids', $data)) {
            $record->syncPermissions($this->resolvePermissionNames($data['permission_ids'] ?? []));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('darejer.admin.roles.index')
            ->with('flash', ['type' => 'success', 'message' => __darejer('Role updated.')]);
    }

    public function destroy(int $role)
    {
        $this->authorizePermission('system.role.update');

        $record = SpatieRole::query()->findOrFail($role);

        if ($record->name === 'super-admin') {
            abort(403, __darejer('The super-admin role cannot be deleted.'));
        }

        $record->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('darejer.admin.roles.index')
            ->with('flash', ['type' => 'success', 'message' => __darejer('Role deleted.')]);
    }

    public function form(): Form
    {
        return Form::make('default')
            ->breadcrumbs([
                ['label' => __darejer('Administration')],
                ['label' => __darejer('Roles'), 'url' => route('darejer.admin.roles.index')],
            ])
            ->components([
                TextInput::make('name')->label(__darejer('Name'))->required()->maxLength(125),
                TextInput::make('guard_name')->label(__darejer('Guard'))->required()->maxLength(125)
                    ->default($this->defaultGuard()),
                Combobox::make('permission_ids')
                    ->label(__darejer('Permissions'))
                    ->multiple()
                    ->model(SpatiePermission::class, 'id', 'name')
                    ->fullWidth(),
            ])
            ->sections([
                Section::make(__darejer('General'))->components(['name', 'guard_name']),
                Section::make(__darejer('Permissions'))->components(['permission_ids']),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateRequest(Request $request, ?int $roleId = null): array
    {
        $table = config('permission.table_names.roles', 'roles');

        return $request->validate([
            'name' => [
                'required', 'string', 'max:125',
                Rule::unique($table, 'name')
                    ->ignore($roleId)
                    ->where(fn ($q) => $q->where('guard_name', $request->input('guard_name', $this->defaultGuard()))),
            ],
            'guard_name' => ['nullable', 'string', 'max:125'],
            'permission_ids' => ['sometimes', 'array'],
            'permission_ids.*' => ['integer', 'exists:'.config('permission.table_names.permissions', 'permissions').',id'],
        ]);
    }

    /** @param  array<int, int|string>  $ids */
    protected function resolvePermissionNames(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return SpatiePermission::query()
            ->whereIn('id', array_map('intval', $ids))
            ->pluck('name')
            ->all();
    }

    protected function authorizePermission(string $permission): void
    {
        $user = auth()->user();
        if (! $user || ! ($user->can($permission) || (method_exists($user, 'hasRole') && $user->hasRole('super-admin')))) {
            abort(403);
        }
    }

    protected function defaultGuard(): string
    {
        return (string) config('darejer.permissions.guard', 'web');
    }
}
