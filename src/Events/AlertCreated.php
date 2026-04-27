<?php

declare(strict_types=1);

namespace Darejer\Events;

use Darejer\Models\Alert;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast over the recipient's private channel when a new alert is
 * persisted. Designed for Laravel Reverb (the recommended driver) but works
 * with any broadcaster the host configures — Pusher, Ably, log, null. If
 * broadcasting is not configured, the dispatch is a harmless no-op.
 *
 * Channel:  `darejer.alerts.{userId}` (private)
 * Event:    `alert.created`
 * Payload:  the same shape as the REST endpoint returns, so the frontend
 *           handler can prepend it to the list directly.
 */
class AlertCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Alert $alert) {}

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('darejer.alerts.'.$this->alert->user_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'alert.created';
    }

    /** @return array{alert: array<string, mixed>} */
    public function broadcastWith(): array
    {
        return [
            'alert' => $this->alert->toFrontend(),
        ];
    }
}
