<?php

declare(strict_types=1);

namespace Darejer\Http\Controllers\Admin;

use Darejer\Actions\DeleteAction;
use Darejer\Actions\LinkAction;
use Darejer\Components\Combobox;
use Darejer\Components\TextInput;
use Darejer\Components\Toggle;
use Darejer\DataGrid\Column;
use Darejer\DataGrid\Filter;
use Darejer\DataGrid\RowAction;
use Darejer\DataTable\DataTable;
use Darejer\Forms\Form;
use Darejer\Http\Controllers\DarejerController;
use Darejer\Routing\RoutePattern;
use Darejer\Screen\Section;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Response;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Admin → Users. CRUD over the host's `User` model with Spatie role
 * assignment. The User model is resolved via `config('auth.providers.users.model')`
 * so the screen works for any host that wires Fortify into Darejer.
 */
class UserController extends DarejerController
{
    protected ?string $resource = 'users';

    protected ?string $routeName = 'darejer.admin.users';

    protected ?string $routePrefix = 'darejer/admin';

    public function index(Request $request): Response
    {
        $this->authorizePermission('system.user.viewAny');

        return DataTable::make($this->userModel())
            ->title(__darejer('Users'))
            ->breadcrumbs([
                ['label' => __darejer('Administration')],
                ['label' => __darejer('Users')],
            ])
            ->columns([
                Column::make('id')->label('#')->width('80px')->sortable(),
                Column::make('username')->label(__darejer('Username'))->sortable()->searchable(),
                Column::make('email')->label(__darejer('Email'))->sortable()->searchable(),
                Column::make('roles_csv')->label(__darejer('Roles'))
                    ->displayUsing(fn ($user) => $user->roles->pluck('name')->join(', ') ?: '—'),
                Column::make('is_super_admin')->label(__darejer('Super Admin'))
                    ->badge(['1' => 'success', '0' => 'muted'])
                    ->displayUsing(fn ($user) => (bool) ($user->is_super_admin ?? false) ? '1' : '0'),
                Column::make('created_at')->label(__darejer('Created'))->sortable()->dateTime(),
            ])
            ->filters([
                Filter::text('username')->label(__darejer('Username')),
                Filter::text('email')->label(__darejer('Email')),
            ])
            ->headerActions([
                LinkAction::make(__darejer('New User'))
                    ->url(route('darejer.admin.users.create'))
                    ->icon('Plus')
                    ->variant('default')
                    ->canSee('system.user.create'),
            ])
            ->rowActions([
                RowAction::edit(RoutePattern::row('darejer.admin.users.edit'))
                    ->canSee('system.user.update'),
                RowAction::delete(RoutePattern::row('darejer.admin.users.destroy'))
                    ->canSee('system.user.delete'),
            ])
            ->with(['roles'])
            ->defaultSort('id', 'desc')
            ->render($request);
    }

    public function create(): Response
    {
        $this->authorizePermission('system.user.create');

        $userModel = $this->userModel();

        return $this->form()
            ->title(__darejer('New User'))
            ->record(new $userModel)
            ->save(route('darejer.admin.users.store'), 'POST')
            ->cancel(route('darejer.admin.users.index'))
            ->renderAsScreen();
    }

    public function edit(int $user): Response
    {
        $this->authorizePermission('system.user.update');

        $record = $this->userModel()::query()->with('roles')->findOrFail($user);

        return $this->form()
            ->title(__darejer('Edit User').' — '.$record->username)
            ->record(array_merge($record->toArray(), [
                'role_ids' => $record->roles->pluck('id')->all(),
                'password' => '',
            ]))
            ->save(route('darejer.admin.users.update', $record->id), 'PUT')
            ->cancel(route('darejer.admin.users.index'))
            ->addActions([
                DeleteAction::make(__darejer('Delete'))
                    ->url(route('darejer.admin.users.destroy', $record->id))
                    ->canSee(fn () => auth()->user()?->can('system.user.delete')),
            ])
            ->renderAsScreen();
    }

    public function store(Request $request)
    {
        $this->authorizePermission('system.user.create');

        $data = $this->validateRequest($request);

        $userModel = $this->userModel();
        $user = new $userModel;
        $user->fill([
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        if (array_key_exists('is_super_admin', $user->getFillable() ?: [])
            || in_array('is_super_admin', $user->getFillable(), true)) {
            $user->is_super_admin = (bool) ($data['is_super_admin'] ?? false);
        }

        $user->save();

        if ($this->canAssignRoles()) {
            $user->syncRoles($this->resolveRoleNames($data['role_ids'] ?? []));
        }

        return redirect()
            ->route('darejer.admin.users.index')
            ->with('flash', ['type' => 'success', 'message' => __darejer('User created.')]);
    }

    public function update(Request $request, int $user)
    {
        $this->authorizePermission('system.user.update');

        $record = $this->userModel()::query()->findOrFail($user);
        $data = $this->validateRequest($request, $record);

        $record->username = $data['username'];
        $record->email = $data['email'];

        if (! empty($data['password'])) {
            $record->password = Hash::make($data['password']);
        }

        if (in_array('is_super_admin', $record->getFillable(), true)
            && $request->user()?->can('system.user.assign-roles')) {
            $record->is_super_admin = (bool) ($data['is_super_admin'] ?? false);
        }

        $record->save();

        if ($this->canAssignRoles() && array_key_exists('role_ids', $data)) {
            $record->syncRoles($this->resolveRoleNames($data['role_ids'] ?? []));
        }

        return redirect()
            ->route('darejer.admin.users.index')
            ->with('flash', ['type' => 'success', 'message' => __darejer('User updated.')]);
    }

    public function destroy(int $user)
    {
        $this->authorizePermission('system.user.delete');

        $record = $this->userModel()::query()->findOrFail($user);

        if (auth()->id() === $record->id) {
            abort(422, __darejer('You cannot delete your own account.'));
        }

        $record->delete();

        return redirect()
            ->route('darejer.admin.users.index')
            ->with('flash', ['type' => 'success', 'message' => __darejer('User deleted.')]);
    }

    public function form(): Form
    {
        $userModel = $this->userModel();
        $hasSuperAdmin = in_array('is_super_admin', (new $userModel)->getFillable(), true);

        $components = [
            TextInput::make('username')->label(__darejer('Username'))->required()->maxLength(191),
            TextInput::make('email')->label(__darejer('Email'))->email()->required()->maxLength(191),
            TextInput::make('password')->label(__darejer('Password'))->password()
                ->hint(__darejer('Leave blank to keep the current password.')),
            TextInput::make('password_confirmation')->label(__darejer('Confirm Password'))->password(),
        ];

        if ($this->canAssignRoles()) {
            $components[] = Combobox::make('role_ids')
                ->label(__darejer('Roles'))
                ->multiple()
                ->model(SpatieRole::class, 'id', 'name')
                ->canSee('system.user.assign-roles');
        }

        if ($hasSuperAdmin) {
            $components[] = Toggle::make('is_super_admin')
                ->label(__darejer('Super Admin'))
                ->hint(__darejer('Bypasses all permission checks.'))
                ->canSee('system.user.assign-roles');
        }

        return Form::make('default')
            ->breadcrumbs([
                ['label' => __darejer('Administration')],
                ['label' => __darejer('Users'), 'url' => route('darejer.admin.users.index')],
            ])
            ->components($components)
            ->sections([
                Section::make(__darejer('Identity'))->components(['username', 'email']),
                Section::make(__darejer('Password'))->components(['password', 'password_confirmation']),
                Section::make(__darejer('Access'))
                    ->components(array_values(array_filter([
                        $this->canAssignRoles() ? 'role_ids' : null,
                        $hasSuperAdmin ? 'is_super_admin' : null,
                    ]))),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateRequest(Request $request, ?Model $existing = null): array
    {
        $userModel = $this->userModel();
        $table = (new $userModel)->getTable();
        $userId = $existing?->getKey();
        $isUpdate = $existing !== null;

        return $request->validate([
            'username' => [
                'required', 'string', 'min:3', 'max:191', 'regex:/^[A-Za-z]+$/',
                Rule::unique($table, 'username')->ignore($userId)->whereNull('deleted_at'),
            ],
            'email' => [
                'required', 'email', 'max:191',
                Rule::unique($table, 'email')->ignore($userId)->whereNull('deleted_at'),
            ],
            'password' => array_filter([
                $isUpdate ? 'nullable' : 'required',
                'string',
                'min:8',
                'confirmed',
            ]),
            'role_ids' => ['sometimes', 'array'],
            'role_ids.*' => ['integer', 'exists:'.config('permission.table_names.roles', 'roles').',id'],
            'is_super_admin' => ['sometimes', 'boolean'],
        ], [
            'username.regex' => __darejer('The username may only contain English letters.'),
        ]);
    }

    protected function authorizePermission(string $permission): void
    {
        $user = auth()->user();
        if (! $user || ! ($user->can($permission) || (method_exists($user, 'hasRole') && $user->hasRole('super-admin')))) {
            abort(403);
        }
    }

    protected function canAssignRoles(): bool
    {
        $user = auth()->user();

        return $user
            && ($user->can('system.user.assign-roles')
                || (method_exists($user, 'hasRole') && $user->hasRole('super-admin')));
    }

    /** @param  array<int, int|string>  $roleIds */
    protected function resolveRoleNames(array $roleIds): array
    {
        if ($roleIds === []) {
            return [];
        }

        return SpatieRole::query()
            ->whereIn('id', array_map('intval', $roleIds))
            ->pluck('name')
            ->all();
    }

    /** @return class-string<Model> */
    protected function userModel(): string
    {
        $model = config('auth.providers.users.model');

        if (! is_string($model) || ! class_exists($model)) {
            abort(500, 'config(auth.providers.users.model) is not a valid class.');
        }

        return $model;
    }
}
