<?php

namespace Darejer\DataGrid;

use Closure;

class RowAction
{
    protected string $label;

    protected string $icon = '';

    protected string $type = 'link';

    protected ?string $urlPattern = null;

    protected bool $dialog = false;

    protected ?string $confirm = null;

    protected string $variant = 'ghost';

    protected mixed $canSeeCheck = null;

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

    public static function delete(string $urlPattern): static
    {
        return (new static('Delete'))
            ->icon('Trash2')
            ->type('delete')
            ->variant('destructive')
            ->confirm('Are you sure you want to delete this record?')
            ->url($urlPattern);
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

    public function toArray(): array
    {
        return array_filter([
            'label' => $this->label,
            'icon' => $this->icon ?: null,
            'type' => $this->type,
            'urlPattern' => $this->urlPattern,
            'dialog' => $this->dialog ?: null,
            'confirm' => $this->confirm,
            'variant' => $this->variant,
        ], fn ($v) => $v !== null && $v !== false);
    }
}
