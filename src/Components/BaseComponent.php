<?php

namespace Darejer\Components;

use Closure;
use Darejer\Screen\Contracts\Componentable;
use Illuminate\Support\Facades\Gate;

abstract class BaseComponent implements Componentable
{
    protected string $name;

    protected string $label = '';

    protected bool $required = false;

    protected ?string $hint = null;

    protected ?string $tooltip = null;

    protected mixed $default = null;

    protected ?array $dependOn = null;

    protected bool $fullWidth = false;

    /** Permission string or closure. null = always visible */
    protected mixed $canSeeCheck = null;

    protected function __construct(string $name)
    {
        $this->name = $name;
    }

    // ── Factory ──────────────────────────────────────────────────────────────

    public static function make(string $name): static
    {
        return new static($name);
    }

    // ── Fluent setters ───────────────────────────────────────────────────────

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function required(bool $required = true): static
    {
        $this->required = $required;

        return $this;
    }

    public function hint(string $hint): static
    {
        $this->hint = $hint;

        return $this;
    }

    public function tooltip(string $tooltip): static
    {
        $this->tooltip = $tooltip;

        return $this;
    }

    public function default(mixed $default): static
    {
        $this->default = $default;

        return $this;
    }

    /** Make the component span the entire form grid row (col-span-full). */
    public function fullWidth(bool $fullWidth = true): static
    {
        $this->fullWidth = $fullWidth;

        return $this;
    }

    /**
     * Show this component only when `$field` equals `$value`.
     *
     *   ->dependOn('status', 'archived')
     */
    public function dependOn(string $field, mixed $value): static
    {
        $this->dependOn = [
            'field' => $field,
            'operator' => 'eq',
            'value' => $value,
        ];

        return $this;
    }

    /**
     * Show when `$field` does NOT equal `$value`.
     */
    public function dependOnNot(string $field, mixed $value): static
    {
        $this->dependOn = [
            'field' => $field,
            'operator' => 'neq',
            'value' => $value,
        ];

        return $this;
    }

    /**
     * Show when `$field` value is non-empty (not null, '', 0, false, []).
     */
    public function dependOnNotEmpty(string $field): static
    {
        $this->dependOn = [
            'field' => $field,
            'operator' => 'notEmpty',
        ];

        return $this;
    }

    /**
     * Show when `$field` value is empty.
     */
    public function dependOnEmpty(string $field): static
    {
        $this->dependOn = [
            'field' => $field,
            'operator' => 'empty',
        ];

        return $this;
    }

    /**
     * Show when `$field` value is in `$values`.
     */
    public function dependOnIn(string $field, array $values): static
    {
        $this->dependOn = [
            'field' => $field,
            'operator' => 'in',
            'value' => $values,
        ];

        return $this;
    }

    /**
     * Show when `$field` value is NOT in `$values`.
     */
    public function dependOnNotIn(string $field, array $values): static
    {
        $this->dependOn = [
            'field' => $field,
            'operator' => 'notIn',
            'value' => $values,
        ];

        return $this;
    }

    /**
     * Combine multiple conditions with AND (default) or OR logic.
     *
     *   ->dependOnConditions([
     *       ['field' => 'type',     'operator' => 'eq', 'value' => 'physical'],
     *       ['field' => 'in_stock', 'operator' => 'eq', 'value' => true],
     *   ], logic: 'and')
     */
    public function dependOnConditions(array $conditions, string $logic = 'and'): static
    {
        $this->dependOn = [
            'conditions' => $conditions,
            'logic' => $logic,
        ];

        return $this;
    }

    /**
     * Control visibility by permission string or closure.
     *
     * Examples:
     *   ->canSee('products.edit')
     *   ->canSee(fn () => auth()->user()->isAdmin())
     */
    public function canSee(string|Closure $permission): static
    {
        $this->canSeeCheck = $permission;

        return $this;
    }

    // ── Visibility check ─────────────────────────────────────────────────────

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

    /**
     * Returns null if component should be stripped from props (not visible).
     */
    public function toArray(): ?array
    {
        if (! $this->isVisible()) {
            return null;
        }

        return array_filter([
            'type' => $this->componentType(),
            'name' => $this->name,
            'label' => $this->label,
            'required' => $this->required,
            'hint' => $this->hint,
            'tooltip' => $this->tooltip,
            'default' => $this->default,
            'dependOn' => $this->dependOn,
            'fullWidth' => $this->fullWidth ?: null,
            ...$this->componentProps(),
        ], fn ($v) => $v !== null);
    }

    /**
     * The Vue component type string. e.g. 'TextInput', 'Combobox', 'DataGrid'
     */
    abstract protected function componentType(): string;

    /**
     * Additional props specific to this component type.
     */
    protected function componentProps(): array
    {
        return [];
    }
}
