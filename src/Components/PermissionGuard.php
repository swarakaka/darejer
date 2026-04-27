<?php

namespace Darejer\Components;

use Closure;
use Darejer\Screen\Contracts\Componentable;

/**
 * Wraps a group of components behind a permission check.
 * If the user lacks the permission, none of the child components
 * are sent to the frontend.
 *
 *   PermissionGuard::make('products.admin')->components([...])
 */
class PermissionGuard implements Componentable
{
    protected mixed $permission;

    /** @var Componentable[] */
    protected array $guarded = [];

    protected function __construct(string|Closure $permission)
    {
        $this->permission = $permission;
    }

    public static function make(string|Closure $permission): static
    {
        return new static($permission);
    }

    /**
     * @param  Componentable[]  $components
     */
    public function components(array $components): static
    {
        $this->guarded = $components;

        return $this;
    }

    protected function isAllowed(): bool
    {
        if ($this->permission instanceof Closure) {
            return (bool) ($this->permission)();
        }

        if (! auth()->check()) {
            return false;
        }

        $user = auth()->user();

        if (method_exists($user, 'hasRole') && $user->hasRole('super-admin')) {
            return true;
        }

        return $user->can($this->permission);
    }

    /**
     * Returns a `__guard__` marker that Screen::serializeComponents()
     * unwraps into the flat component list. Returns null when the user
     * lacks the permission — completely stripping the guard + its children
     * from the Inertia payload.
     */
    public function toArray(): ?array
    {
        if (! $this->isAllowed()) {
            return null;
        }

        return [
            'type' => '__guard__',
            'components' => collect($this->guarded)
                ->map(fn (Componentable $c) => $c->toArray())
                ->filter()
                ->values()
                ->all(),
        ];
    }
}
