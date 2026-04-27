<?php

namespace Darejer\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Base policy for Darejer resources.
 *
 *   class ProductPolicy extends DarejerPolicy
 *   {
 *       protected string $resource = 'products';
 *   }
 *
 * Maps CRUD methods to permissions named "{resource}.viewAny",
 * "{resource}.view", "{resource}.create", etc.
 */
abstract class DarejerPolicy
{
    use HandlesAuthorization;

    protected string $resource = '';

    /**
     * Super-admin bypass — runs before every policy method.
     */
    public function before($user, string $ability): ?bool
    {
        if (method_exists($user, 'hasRole') && $user->hasRole('super-admin')) {
            return true;
        }

        return null;
    }

    public function viewAny($user): bool
    {
        return $user->can("{$this->resource}.viewAny");
    }

    public function view($user, $model): bool
    {
        return $user->can("{$this->resource}.view");
    }

    public function create($user): bool
    {
        return $user->can("{$this->resource}.create");
    }

    public function update($user, $model): bool
    {
        return $user->can("{$this->resource}.update");
    }

    public function delete($user, $model): bool
    {
        return $user->can("{$this->resource}.delete");
    }

    public function restore($user, $model): bool
    {
        return $user->can("{$this->resource}.restore");
    }

    public function forceDelete($user, $model): bool
    {
        return $user->can("{$this->resource}.forceDelete");
    }
}
