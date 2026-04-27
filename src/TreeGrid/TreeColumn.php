<?php

namespace Darejer\TreeGrid;

class TreeColumn
{
    protected string $field;

    protected string $label = '';

    protected bool $sortable = false;

    protected ?string $width = null;

    protected string $align = 'left';

    protected bool $isTree = false;

    protected function __construct(string $field)
    {
        $this->field = $field;
        $this->label = ucfirst(str_replace('_', ' ', $field));
    }

    public static function make(string $field): static
    {
        return new static($field);
    }

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function sortable(): static
    {
        $this->sortable = true;

        return $this;
    }

    public function width(string $width): static
    {
        $this->width = $width;

        return $this;
    }

    public function alignRight(): static
    {
        $this->align = 'right';

        return $this;
    }

    public function alignCenter(): static
    {
        $this->align = 'center';

        return $this;
    }

    /**
     * Mark this as the tree column — shows the expand/collapse arrow.
     */
    public function tree(): static
    {
        $this->isTree = true;

        return $this;
    }

    public function toArray(): array
    {
        return array_filter([
            'field' => $this->field,
            'label' => $this->label,
            'sortable' => $this->sortable ?: null,
            'width' => $this->width,
            'align' => $this->align !== 'left' ? $this->align : null,
            'isTree' => $this->isTree ?: null,
        ], fn ($v) => $v !== null && $v !== false);
    }
}
