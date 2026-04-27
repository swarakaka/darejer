<?php

namespace Darejer\Components;

class TranslatableTextarea extends BaseComponent
{
    protected string $placeholder = '';

    protected int $rows = 4;

    protected bool $readonly = false;

    protected bool $disabled = false;

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

    protected function componentType(): string
    {
        return 'TranslatableTextarea';
    }

    protected function componentProps(): array
    {
        return [
            'translatable' => true,
            'placeholder' => $this->placeholder ?: null,
            'rows' => $this->rows,
            'readonly' => $this->readonly ?: null,
            'disabled' => $this->disabled ?: null,
        ];
    }
}
