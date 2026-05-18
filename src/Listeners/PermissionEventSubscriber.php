<?php

declare(strict_types=1);

namespace Darejer\Listeners;

use Darejer\Support\AuditWriter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Collection;
use Spatie\Permission\Events\PermissionAttached;
use Spatie\Permission\Events\PermissionDetached;
use Spatie\Permission\Events\RoleAttached;
use Spatie\Permission\Events\RoleDetached;

/**
 * Records role / permission grants and revocations to `audit_logs`.
 *
 * Requires `permission.events_enabled = true` in `config/permission.php`.
 * Without that, Spatie never fires these events.
 */
final class PermissionEventSubscriber
{
    public function handleRoleAttached(RoleAttached $event): void
    {
        $this->write('auth.role.attached', $event->model, $event->rolesOrIds);
    }

    public function handleRoleDetached(RoleDetached $event): void
    {
        $this->write('auth.role.detached', $event->model, $event->rolesOrIds);
    }

    public function handlePermissionAttached(PermissionAttached $event): void
    {
        $this->write('auth.permission.attached', $event->model, $event->permissionsOrIds);
    }

    public function handlePermissionDetached(PermissionDetached $event): void
    {
        $this->write('auth.permission.detached', $event->model, $event->permissionsOrIds);
    }

    private function write(string $event, Model $subject, mixed $idsOrModels): void
    {
        AuditWriter::write(
            event: $event,
            subjectType: $subject::class,
            subjectId: $subject->getKey(),
            payload: ['targets' => $this->normalize($idsOrModels)],
        );
    }

    /**
     * Coerce Spatie's mixed payload into a flat list of identifiers / names.
     *
     * @return array<int|string>
     */
    private function normalize(mixed $value): array
    {
        if ($value instanceof Collection) {
            $value = $value->all();
        }

        if ($value instanceof Model) {
            return [$value->getKey()];
        }

        if (! is_array($value)) {
            return [$value];
        }

        return array_map(
            static fn ($item) => $item instanceof Model ? $item->getKey() : $item,
            $value,
        );
    }

    /**
     * @return array<class-string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            RoleAttached::class => 'handleRoleAttached',
            RoleDetached::class => 'handleRoleDetached',
            PermissionAttached::class => 'handlePermissionAttached',
            PermissionDetached::class => 'handlePermissionDetached',
        ];
    }
}
