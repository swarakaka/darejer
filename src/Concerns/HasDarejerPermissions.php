<?php

namespace Darejer\Concerns;

/**
 * Add this trait to the User model alongside Spatie's HasRoles.
 * It provides Darejer-specific permission helpers.
 */
trait HasDarejerPermissions
{
    /**
     * Super-admins bypass all permission checks in Darejer.
     */
    public function isSuperAdmin(): bool
    {
        if (! method_exists($this, 'hasRole')) {
            return false;
        }

        return $this->hasRole('super-admin');
    }

    /**
     * Permission check that honours the super-admin bypass.
     */
    public function darejerCan(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->can($permission);
    }

    /**
     * Flat list of permission names (for Inertia shared props).
     */
    public function darejerPermissions(): array
    {
        if (! method_exists($this, 'getAllPermissions')) {
            return [];
        }

        return $this->getAllPermissions()->pluck('name')->toArray();
    }

    /**
     * Flat list of role names (for Inertia shared props).
     */
    public function darejerRoles(): array
    {
        if (! method_exists($this, 'getRoleNames')) {
            return [];
        }

        return $this->getRoleNames()->toArray();
    }
}
