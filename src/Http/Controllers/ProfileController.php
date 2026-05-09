<?php

declare(strict_types=1);

namespace Darejer\Http\Controllers;

use Darejer\Components\TextInput;
use Darejer\Forms\Form;
use Darejer\Screen\Section;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Contracts\UpdatesUserPasswords;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

/**
 * Self-service profile editor for the currently authenticated user.
 *
 * Linked from the topbar user dropdown. Reuses the standard Form/Screen
 * builder so the page layout matches the rest of the admin surface.
 * Actual persistence is delegated to Fortify's UpdatesUserProfileInformation
 * and UpdatesUserPasswords contracts so host overrides keep working.
 */
class ProfileController extends Controller
{
    public function edit(): Response
    {
        $user = auth()->user();
        abort_unless($user, 403);

        return $this->form()
            ->title(__darejer('Edit Profile'))
            ->record(array_merge($user->only(['username', 'email']), [
                'password' => '',
                'password_confirmation' => '',
            ]))
            ->save(route('darejer.profile.update'), 'PUT')
            ->cancel(route('darejer.dashboard'))
            ->renderAsScreen();
    }

    public function update(Request $request, UpdatesUserProfileInformation $updateProfile, UpdatesUserPasswords $updatePassword)
    {
        $user = $request->user();
        abort_unless($user, 403);

        $userTable = $user->getTable();

        $data = $request->validate([
            'username' => [
                'required', 'string', 'min:3', 'max:191', 'regex:/^[A-Za-z]+$/',
                Rule::unique($userTable, 'username')->ignore($user->getKey())->whereNull('deleted_at'),
            ],
            'email' => [
                'required', 'email', 'max:191',
                Rule::unique($userTable, 'email')->ignore($user->getKey())->whereNull('deleted_at'),
            ],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ], [
            'username.regex' => __darejer('The username may only contain English letters.'),
        ]);

        $updateProfile->update($user, [
            'username' => $data['username'],
            'email' => $data['email'],
        ]);

        if (! empty($data['password'])) {
            $user->forceFill([
                'password' => Hash::make($data['password']),
            ])->save();
        }

        Inertia::flash('success', __darejer('Profile updated.'));

        return redirect()->route('darejer.profile.edit');
    }

    protected function form(): Form
    {
        return Form::make('default')
            ->breadcrumbs([
                ['label' => __darejer('Profile')],
            ])
            ->components([
                TextInput::make('username')->label(__darejer('Username'))->required()->maxLength(191),
                TextInput::make('email')->label(__darejer('Email'))->email()->required()->maxLength(191),
                TextInput::make('password')->label(__darejer('Password'))->password()
                    ->hint(__darejer('Leave blank to keep the current password.')),
                TextInput::make('password_confirmation')->label(__darejer('Confirm Password'))->password(),
            ])
            ->sections([
                Section::make(__darejer('Identity'))->components(['username', 'email']),
                Section::make(__darejer('Password'))->components(['password', 'password_confirmation']),
            ]);
    }
}
