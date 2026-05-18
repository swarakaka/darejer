<?php

declare(strict_types=1);

namespace Darejer\Listeners;

use Darejer\Support\AuditWriter;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Events\Dispatcher;
use Laravel\Fortify\Events\RecoveryCodeReplaced;
use Laravel\Fortify\Events\TwoFactorAuthenticationChallenged;
use Laravel\Fortify\Events\TwoFactorAuthenticationConfirmed;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationEnabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationFailed;

/**
 * Bridges Laravel auth + Fortify events into the `audit_logs` table so
 * "who logged in when, from where" is reconstructible after the fact.
 *
 * Listeners are registered once via {@see subscribe()}; payloads are
 * intentionally minimal — IP and user-agent are already captured by
 * AuditWriter.
 */
final class AuthEventSubscriber
{
    public function handleLogin(Login $event): void
    {
        AuditWriter::write(
            event: 'auth.login',
            subjectType: $event->user::class,
            subjectId: $event->user->getKey(),
            payload: ['guard' => $event->guard, 'remember' => $event->remember],
        );
    }

    public function handleLogout(Logout $event): void
    {
        if ($event->user === null) {
            return;
        }

        AuditWriter::write(
            event: 'auth.logout',
            subjectType: $event->user::class,
            subjectId: $event->user->getKey(),
            payload: ['guard' => $event->guard],
        );
    }

    public function handleFailed(Failed $event): void
    {
        AuditWriter::write(
            event: 'auth.failed',
            subjectType: $event->user ? $event->user::class : null,
            subjectId: $event->user?->getKey(),
            payload: [
                'guard' => $event->guard,
                'username' => $event->credentials['username'] ?? $event->credentials['email'] ?? null,
            ],
        );
    }

    public function handleLockout(Lockout $event): void
    {
        AuditWriter::write(
            event: 'auth.lockout',
            payload: [
                'ip' => $event->request->ip(),
                'username' => $event->request->input('username') ?? $event->request->input('email'),
            ],
        );
    }

    public function handlePasswordReset(PasswordReset $event): void
    {
        AuditWriter::write(
            event: 'auth.password_reset',
            subjectType: $event->user::class,
            subjectId: $event->user->getKey(),
        );
    }

    public function handleTwoFactorEnabled(TwoFactorAuthenticationEnabled $event): void
    {
        AuditWriter::write(
            event: 'auth.2fa.enabled',
            subjectType: $event->user::class,
            subjectId: $event->user->getKey(),
        );
    }

    public function handleTwoFactorConfirmed(TwoFactorAuthenticationConfirmed $event): void
    {
        AuditWriter::write(
            event: 'auth.2fa.confirmed',
            subjectType: $event->user::class,
            subjectId: $event->user->getKey(),
        );
    }

    public function handleTwoFactorDisabled(TwoFactorAuthenticationDisabled $event): void
    {
        AuditWriter::write(
            event: 'auth.2fa.disabled',
            subjectType: $event->user::class,
            subjectId: $event->user->getKey(),
        );
    }

    public function handleTwoFactorChallenged(TwoFactorAuthenticationChallenged $event): void
    {
        AuditWriter::write(
            event: 'auth.2fa.challenged',
            subjectType: $event->user::class,
            subjectId: $event->user->getKey(),
        );
    }

    public function handleTwoFactorFailed(TwoFactorAuthenticationFailed $event): void
    {
        AuditWriter::write(
            event: 'auth.2fa.failed',
            subjectType: $event->user::class,
            subjectId: $event->user->getKey(),
        );
    }

    public function handleRecoveryCodeReplaced(RecoveryCodeReplaced $event): void
    {
        AuditWriter::write(
            event: 'auth.2fa.recovery_code_used',
            subjectType: $event->user::class,
            subjectId: $event->user->getKey(),
        );
    }

    /**
     * @return array<class-string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class => 'handleLogin',
            Logout::class => 'handleLogout',
            Failed::class => 'handleFailed',
            Lockout::class => 'handleLockout',
            PasswordReset::class => 'handlePasswordReset',
            TwoFactorAuthenticationEnabled::class => 'handleTwoFactorEnabled',
            TwoFactorAuthenticationConfirmed::class => 'handleTwoFactorConfirmed',
            TwoFactorAuthenticationDisabled::class => 'handleTwoFactorDisabled',
            TwoFactorAuthenticationChallenged::class => 'handleTwoFactorChallenged',
            TwoFactorAuthenticationFailed::class => 'handleTwoFactorFailed',
            RecoveryCodeReplaced::class => 'handleRecoveryCodeReplaced',
        ];
    }
}
