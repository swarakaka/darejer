<?php

namespace Darejer\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

/**
 * Server-side visibility for actions, components, and any screen element that
 * should be conditionally rendered. Replaces the ternary `condition ? X::make() : null`
 * pattern with a fluent `->visible(...)` call.
 *
 * Accepts either:
 *   - a permission string (checked via Gate / $user->can() with super-admin bypass), or
 *   - a closure receiving (record, user) — useful for row/state-aware checks.
 *
 * Examples:
 *
 *     ButtonAction::make(__('Soft-Close'))
 *         ->visible('accounting.period.softclose')
 *
 *     ButtonAction::make(__('Soft-Close'))
 *         ->visible(fn ($period, $user) => $period->status === PeriodStatus::Open
 *             && $user?->can('accounting.period.softclose'))
 */
trait HasVisibility
{
    /** Permission string or Closure(record, user) => bool. null = always visible. */
    protected mixed $visibleCheck = null;

    /** Record context for closure-based checks. Injected by the parent screen / form. */
    protected Model|array|null $visibilityRecord = null;

    /**
     * Set the visibility rule.
     *
     * @param  string|Closure(Model|array|null $record, mixed $user): bool  $check
     */
    public function visible(string|Closure $check): static
    {
        $this->visibleCheck = $check;

        return $this;
    }

    /**
     * Inject the parent record so closure-based checks can evaluate row state.
     * Called by Screen / Form / DataTable during serialization.
     */
    public function withVisibilityRecord(Model|array|null $record): static
    {
        $this->visibilityRecord = $record;

        return $this;
    }

    /**
     * True when no visible() rule was set or the rule passes.
     */
    protected function passesVisibility(): bool
    {
        if ($this->visibleCheck === null) {
            return true;
        }

        $user = auth()->user();

        if ($this->visibleCheck instanceof Closure) {
            return (bool) ($this->visibleCheck)($this->visibilityRecord, $user);
        }

        if ($user === null) {
            return false;
        }

        // Super-admin bypasses all permission checks.
        if (method_exists($user, 'hasRole') && $user->hasRole('super-admin')) {
            return true;
        }

        return $user->can($this->visibleCheck) || Gate::allows($this->visibleCheck);
    }
}
