<?php

declare(strict_types=1);

use Darejer\Http\Controllers\ProfileController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\UpdatesUserPasswords;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class ProfileTestUser extends Authenticatable
{
    protected $table = 'users';

    protected $guarded = [];

    public $timestamps = false;
}

beforeEach(function (): void {
    Schema::create('users', function (Blueprint $table): void {
        $table->id();
        $table->string('username')->unique();
        $table->string('email')->unique();
        $table->string('password')->nullable();
        $table->softDeletes();
    });

    config()->set('auth.providers.users.model', ProfileTestUser::class);

    $user = ProfileTestUser::query()->create([
        'username' => 'syntax',
        'email' => 'info@syntax.krd',
        'password' => bcrypt('secret123'),
    ]);
    test()->actingAs($user);
    test()->user = $user;

    test()->captured = (object) ['profile' => null, 'password' => null];

    app()->instance(UpdatesUserProfileInformation::class, new class(test()->captured) implements UpdatesUserProfileInformation
    {
        public function __construct(private object $captured) {}

        public function update($user, array $input): void
        {
            $this->captured->profile = $input;
            $user->forceFill($input)->save();
        }
    });

    app()->instance(UpdatesUserPasswords::class, new class(test()->captured) implements UpdatesUserPasswords
    {
        public function __construct(private object $captured) {}

        public function update($user, array $input): void
        {
            $this->captured->password = $input;
        }
    });
});

afterEach(function (): void {
    Schema::dropIfExists('users');
});

function profileUpdateRequest(array $body): Request
{
    $request = Request::create('/darejer/profile', 'PUT', $body);
    $request->setUserResolver(fn () => auth()->user());
    app()->instance('request', $request);

    return $request;
}

it('ignores any submitted username and keeps the original value', function (): void {
    $controller = app(ProfileController::class);

    $controller->update(
        profileUpdateRequest([
            'username' => 'hacker',
            'email' => 'info@syntax.krd',
        ]),
        app(UpdatesUserProfileInformation::class),
        app(UpdatesUserPasswords::class),
    );

    expect(test()->user->fresh()->username)->toBe('syntax');
    expect(test()->captured->profile)->toMatchArray([
        'username' => 'syntax',
        'email' => 'info@syntax.krd',
    ]);
});

it('still allows the user to update their email', function (): void {
    $controller = app(ProfileController::class);

    $controller->update(
        profileUpdateRequest([
            'email' => 'new@syntax.krd',
        ]),
        app(UpdatesUserProfileInformation::class),
        app(UpdatesUserPasswords::class),
    );

    expect(test()->user->fresh()->email)->toBe('new@syntax.krd');
    expect(test()->user->fresh()->username)->toBe('syntax');
});

it('does not require username in the request body', function (): void {
    $controller = app(ProfileController::class);

    $response = $controller->update(
        profileUpdateRequest([
            'email' => 'info@syntax.krd',
        ]),
        app(UpdatesUserProfileInformation::class),
        app(UpdatesUserPasswords::class),
    );

    expect($response->getStatusCode())->toBe(302);
});

it('still validates email format', function (): void {
    $controller = app(ProfileController::class);

    expect(fn () => $controller->update(
        profileUpdateRequest([
            'email' => 'not-an-email',
        ]),
        app(UpdatesUserProfileInformation::class),
        app(UpdatesUserPasswords::class),
    ))->toThrow(ValidationException::class);
});
