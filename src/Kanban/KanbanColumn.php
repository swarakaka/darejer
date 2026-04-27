<?php

namespace Darejer\Kanban;

class KanbanColumn
{
    protected string $value;

    protected string $label;

    protected ?string $color = null;

    protected ?int $limit = null;

    protected bool $locked = false;

    protected function __construct(string $value, string $label)
    {
        $this->value = $value;
        $this->label = $label;
    }

    public static function make(string $value, string $label): static
    {
        return new static($value, $label);
    }

    public function color(string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function limit(int $limit): static
    {
        $this->limit = $limit;

        return $this;
    }

    public function locked(): static
    {
        $this->locked = true;

        return $this;
    }

    public function toArray(): array
    {
        return array_filter([
            'value' => $this->value,
            'label' => $this->label,
            'color' => $this->color,
            'limit' => $this->limit,
            'locked' => $this->locked ?: null,
        ], fn ($v) => $v !== null && $v !== false);
    }
}
