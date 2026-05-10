<?php

namespace Darejer\DataGrid;

use Closure;

class RowAction
{
    protected string $label;

    protected string $icon = '';

    protected string $type = 'link';

    protected ?string $urlPattern = null;

    protected string $method = 'GET';

    protected bool $dialog = false;

    protected ?string $confirm = null;

    protected string $variant = 'ghost';

    protected mixed $canSeeCheck = null;

    protected ?array $dependOn = null;

    protected function __construct(string $label)
    {
        $this->label = $label;
    }

    public static function make(string $label): static
    {
        return new static($label);
    }

    public static function edit(string $urlPattern): static
    {
        return (new static('Edit'))
            ->icon('Pencil')
            ->url($urlPattern);
    }

    public static function view(string $urlPattern): static
    {
        return (new static('View'))
            ->icon('Eye')
            ->url($urlPattern);
    }

    /**
     * Soft-delete row action. Auto-hidden on rows that are already trashed
     * (so the same row action set works for both default and `trashed=with`
     * views without the user seeing an inert delete button).
     */
    public static function delete(string $urlPattern): static
    {
        return (new static('Delete'))
            ->icon('Trash2')
            ->type('delete')
            ->method('DELETE')
            ->variant('destructive')
            ->confirm(__('Are you sure you want to delete this record?'))
            ->url($urlPattern)
            ->dependOn('deleted_at', null);
    }

    /**
     * Restore a soft-deleted row. Visible only when `deleted_at` is set.
     */
    public static function restore(string $urlPattern): static
    {
        return (new static('Restore'))
            ->icon('RotateCcw')
            ->type('restore')
            ->method('PATCH')
            ->variant('ghost')
            ->confirm(__('Are you sure you want to restore this record?'))
            ->url($urlPattern)
            ->dependOnNotEmpty('deleted_at');
    }

    /**
     * Permanently delete a soft-deleted row. Visible only when `deleted_at`
     * is set so it never appears alongside the soft-delete action.
     */
    public static function forceDelete(string $urlPattern): static
    {
        return (new static('Delete permanently'))
            ->icon('Trash')
            ->type('forceDelete')
            ->method('DELETE')
            ->variant('destructive')
            ->confirm(__('This will permanently delete the record. This cannot be undone.'))
            ->url($urlPattern)
            ->dependOnNotEmpty('deleted_at');
    }

    public function icon(string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function type(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function url(string $pattern): static
    {
        $this->urlPattern = $pattern;

        return $this;
    }

    public function method(string $method): static
    {
        $this->method = strtoupper($method);

        return $this;
    }

    public function dialog(bool $dialog = true): static
    {
        $this->dialog = $dialog;

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

    public function canSee(string|Closure $permission): static
    {
        $this->canSeeCheck = $permission;

        return $this;
    }

    /**
     * Show this action only when the row's `$field` matches `$value` using
     * `$operator`. Evaluated client-side via the same `evaluateDependOn`
     * helper used by header / bulk actions.
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

    public function dependOnEmpty(string $field): static
    {
        $this->dependOn = ['field' => $field, 'operator' => 'empty'];

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

    public function toArray(): array
    {
        return array_filter([
            'label' => $this->label,
            'icon' => $this->icon ?: null,
            'type' => $this->type,
            'urlPattern' => $this->urlPattern,
            'method' => $this->method !== 'GET' ? $this->method : null,
            'dialog' => $this->dialog ?: null,
            'confirm' => $this->confirm,
            'variant' => $this->variant,
            'dependOn' => $this->dependOn,
        ], fn ($v) => $v !== null && $v !== false);
    }
}
