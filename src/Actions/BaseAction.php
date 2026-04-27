<?php

namespace Darejer\Actions;

use Closure;
use Darejer\Screen\Contracts\Actionable;
use Illuminate\Support\Facades\Gate;

abstract class BaseAction implements Actionable
{
    protected string $label = '';

    protected ?string $url = null;

    protected string $method = 'GET';

    protected bool $dialog = false;

    protected mixed $canSeeCheck = null;

    protected ?string $icon = null;

    protected ?string $confirm = null;

    protected string $variant = 'default';

    protected bool $disabled = false;

    protected ?string $tooltip = null;

    protected bool $isFullWidth = false;

    protected ?array $dependOn = null;

    protected function __construct(string $label)
    {
        $this->label = $label;
    }

    public static function make(string $label): static
    {
        return new static($label);
    }

    // ── Fluent setters ───────────────────────────────────────────────────────

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function url(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function method(string $method): static
    {
        $this->method = strtoupper($method);

        return $this;
    }

    public function dialog(): static
    {
        $this->dialog = true;

        return $this;
    }

    public function canSee(string|Closure $permission): static
    {
        $this->canSeeCheck = $permission;

        return $this;
    }

    public function icon(string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function confirm(string $message): static
    {
        $this->confirm = $message;

        return $this;
    }

    public function variant(string $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;

        return $this;
    }

    public function tooltip(string $tooltip): static
    {
        $this->tooltip = $tooltip;

        return $this;
    }

    public function fullWidth(): static
    {
        $this->isFullWidth = true;

        return $this;
    }

    /**
     * Show this action only when `$field` matches `$value` using `$operator`.
     * Client-side-only visibility — use `canSee()` for authorization.
     */
    public function dependOn(string $field, mixed $value, string $operator = 'eq'): static
    {
        $this->dependOn = [
            'field' => $field,
            'operator' => $operator,
            'value' => $value,
        ];

        return $this;
    }

    public function dependOnNotEmpty(string $field): static
    {
        $this->dependOn = ['field' => $field, 'operator' => 'notEmpty'];

        return $this;
    }

    public function dependOnIn(string $field, array $values): static
    {
        $this->dependOn = ['field' => $field, 'operator' => 'in', 'value' => $values];

        return $this;
    }

    public function dependOnConditions(array $conditions, string $logic = 'and'): static
    {
        $this->dependOn = ['conditions' => $conditions, 'logic' => $logic];

        return $this;
    }

    // ── Visibility ───────────────────────────────────────────────────────────

    protected function isVisible(): bool
    {
        if ($this->canSeeCheck === null) {
            return true;
        }

        if ($this->canSeeCheck instanceof Closure) {
            return (bool) ($this->canSeeCheck)();
        }

        if (! auth()->check()) {
            return false;
        }

        $user = auth()->user();

        // Super-admin bypasses all permission checks.
        if (method_exists($user, 'hasRole') && $user->hasRole('super-admin')) {
            return true;
        }

        return $user->can($this->canSeeCheck)
            || Gate::allows($this->canSeeCheck);
    }

    // ── Serialization ────────────────────────────────────────────────────────

    public function toArray(): ?array
    {
        if (! $this->isVisible()) {
            return null;
        }

        return array_filter([
            'type' => $this->actionType(),
            'label' => $this->label,
            'url' => $this->url,
            'method' => $this->method,
            'dialog' => $this->dialog,
            'icon' => $this->icon,
            'confirm' => $this->confirm,
            'variant' => $this->variant,
            'disabled' => $this->disabled ?: null,
            'tooltip' => $this->tooltip,
            'fullWidth' => $this->isFullWidth ?: null,
            'dependOn' => $this->dependOn,
            ...$this->actionProps(),
        ], fn ($v) => $v !== null && $v !== false);
    }

    abstract protected function actionType(): string;

    protected function actionProps(): array
    {
        return [];
    }
}
