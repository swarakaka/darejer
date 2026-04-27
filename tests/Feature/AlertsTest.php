<?php

declare(strict_types=1);

use Darejer\Events\AlertCreated;
use Darejer\Http\Controllers\AlertsController;
use Darejer\Models\Alert as AlertModel;
use Darejer\Support\Alert;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;

class AlertTestUser extends Authenticatable
{
    protected $table = 'users';

    protected $guarded = [];

    public $timestamps = false;
}

beforeEach(function (): void {
    Schema::create('users', function (Blueprint $table): void {
        $table->id();
        $table->string('name')->nullable();
        $table->string('email')->nullable();
    });

    Schema::create('darejer_alerts', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('user_id')->index();
        $table->string('level', 16);
        $table->json('message');
        $table->string('link')->nullable();
        $table->json('data')->nullable();
        $table->timestamp('read_at')->nullable()->index();
        $table->timestamps();
    });

    config()->set('auth.providers.users.model', AlertTestUser::class);
    config()->set('darejer.languages', ['en', 'ar']);
    config()->set('darejer.default_language', 'en');

    $user = AlertTestUser::query()->create([
        'name' => 'Recipient',
        'email' => 'r@example.test',
    ]);
    test()->actingAs($user);
    test()->user = $user;
});

function alertRequest(string $method = 'GET', string $uri = '/darejer/alerts'): Request
{
    $request = Request::create($uri, $method);
    $request->setUserResolver(fn () => auth()->user());

    return $request;
}

afterEach(function (): void {
    Schema::dropIfExists('darejer_alerts');
    Schema::dropIfExists('users');
});

it('creates a per-user alert with a translatable message and dispatches the broadcast event', function (): void {
    Event::fake([AlertCreated::class]);

    $alert = Alert::success('Invoice approved');

    expect($alert)->toBeInstanceOf(AlertModel::class);
    expect($alert->level)->toBe('success');
    expect($alert->user_id)->toBe(test()->user->id);
    expect($alert->getTranslation('message', 'en'))->toBe('Invoice approved');

    Event::assertDispatched(AlertCreated::class, fn ($e) => $e->alert->id === $alert->id);
});

it('stores a per-locale message bag when given an array', function (): void {
    $alert = Alert::info(['en' => 'Saved', 'ar' => 'تم الحفظ']);

    expect($alert->getTranslation('message', 'en'))->toBe('Saved');
    expect($alert->getTranslation('message', 'ar'))->toBe('تم الحفظ');
});

it('persists a link so the slideover can navigate to the related record', function (): void {
    $alert = Alert::warning('Invoice changed', link: '/invoices/42');

    expect($alert->link)->toBe('/invoices/42');
    expect($alert->toFrontend())->toMatchArray([
        'level' => 'warning',
        'link' => '/invoices/42',
        'message' => 'Invoice changed',
    ]);
});

it('targets a specific recipient when $to is passed explicitly', function (): void {
    $other = AlertTestUser::query()->create(['name' => 'Other', 'email' => 'o@example.test']);

    $alert = Alert::error('Payment failed', to: $other);

    expect($alert->user_id)->toBe($other->id);
});

it('lists alerts for the auth user with an unread count meta', function (): void {
    Alert::success('A');
    Alert::info('B')->markAsRead();
    Alert::warning('C');

    $controller = new AlertsController;
    $response = $controller->index(alertRequest());

    $payload = $response->getData(true);

    expect($payload['data'])->toHaveCount(3);
    expect($payload['meta']['unread_count'])->toBe(2);
    expect($payload['meta']['total'])->toBe(3);
});

it('marks a single alert as read', function (): void {
    $alert = Alert::success('Hello');

    $controller = new AlertsController;
    $response = $controller->markRead(alertRequest('POST', '/darejer/alerts/'.$alert->id.'/read'), $alert->id);

    expect($response->getData(true)['success'])->toBeTrue();
    expect($alert->fresh()->read_at)->not->toBeNull();
});

it('marks every unread alert as read in one go', function (): void {
    Alert::success('A');
    Alert::success('B');
    Alert::success('C');

    $controller = new AlertsController;
    $controller->markAllRead(alertRequest('POST', '/darejer/alerts/read-all'));

    expect(AlertModel::query()->whereNull('read_at')->count())->toBe(0);
});

it('deletes a single alert and refuses to touch alerts owned by other users', function (): void {
    $mine = Alert::success('Mine');
    $other = AlertTestUser::query()->create(['name' => 'Other', 'email' => 'o@example.test']);
    $theirs = Alert::success('Theirs', to: $other);

    $controller = new AlertsController;
    $controller->destroy(alertRequest('DELETE', '/darejer/alerts/'.$mine->id), $mine->id);

    expect(AlertModel::query()->find($mine->id))->toBeNull();
    expect(AlertModel::query()->find($theirs->id))->not->toBeNull();

    // Cross-user delete is a 404.
    expect(fn () => $controller->destroy(
        alertRequest('DELETE', '/darejer/alerts/'.$theirs->id),
        $theirs->id,
    ))->toThrow(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
});

it('renders the message in the active locale at fetch time', function (): void {
    Alert::success(['en' => 'Saved', 'ar' => 'تم الحفظ']);

    app()->setLocale('ar');

    $controller = new AlertsController;
    $response = $controller->index(alertRequest());

    expect($response->getData(true)['data'][0]['message'])->toBe('تم الحفظ');
});
