<?php

namespace Darejer\Components;

class Textarea extends BaseComponent
{
    protected string $placeholder = '';

    protected int $rows = 4;

    protected bool $readonly = false;

    protected bool $disabled = false;

    protected bool $autoResize = false;

    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function rows(int $rows): static
    {
        $this->rows = $rows;

        return $this;
    }

    public function readonly(bool $readonly = true): static
    {
        $this->readonly = $readonly;

        return $this;
    }

    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;

        return $this;
    }

    public function autoResize(): static
    {
        $this->autoResize = true;

        return $this;
    }

    protected function componentType(): string
    {
        return 'Textarea';
    }

    protected function componentProps(): array
    {
        return [
            'placeholder' => $this->placeholder ?: null,
            'rows' => $this->rows,
            'readonly' => $this->readonly ?: null,
            'disabled' => $this->disabled ?: null,
            'autoResize' => $this->autoResize ?: null,
        ];
    }
}
