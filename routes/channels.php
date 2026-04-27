<?php

use Illuminate\Support\Facades\Broadcast;

/**
 * Darejer broadcast channels.
 *
 * Loaded by `DarejerServiceProvider::boot()` so host apps don't need to
 * touch their own `routes/channels.php` to authorise per-user alert
 * websockets — zero-config.
 */

Broadcast::channel('darejer.alerts.{userId}', function ($user, int $userId): bool {
    return (int) $user->getAuthIdentifier() === $userId;
});
