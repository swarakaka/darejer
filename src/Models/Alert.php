<?php

declare(strict_types=1);

namespace Darejer\Models;

use Darejer\Concerns\HasDarejerTranslatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-user, persisted notification ("Alert").
 *
 * Created through the static `Darejer\Support\Alert` API and broadcast over
 * a private user channel by `Darejer\Events\AlertCreated`. The `message`
 * column is a Spatie Translatable JSON bag, so a single alert can be
 * authored once and read back in any configured locale.
 */
class Alert extends Model
{
    use HasDarejerTranslatable;

    protected $table = 'darejer_alerts';

    /** @var array<int, string> */
    public array $translatable = ['message'];

    protected $fillable = [
        'user_id',
        'level',
        'message',
        'link',
        'data',
        'read_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'data' => 'array',
            'read_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'user_id');
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function markAsRead(): bool
    {
        if ($this->read_at !== null) {
            return false;
        }

        return $this->forceFill(['read_at' => now()])->save();
    }

    /**
     * Frontend-ready payload (used by the controller and broadcast event).
     *
     * @return array{
     *     id:int, level:string, message:string, link:?string,
     *     data:?array<string,mixed>, read_at:?string, created_at:?string
     * }
     */
    public function toFrontend(): array
    {
        return [
            'id' => $this->id,
            'level' => $this->level,
            'message' => $this->getTranslationWithFallback('message'),
            'link' => $this->link,
            'data' => $this->data,
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
