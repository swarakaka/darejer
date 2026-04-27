<?php

namespace Darejer\Components;

class TranslatableInput extends BaseComponent
{
    protected string $placeholder = '';

    protected bool $readonly = false;

    protected bool $disabled = false;

    protected ?int $maxLength = null;

    protected bool $autofocus = false;

    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;

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

    public function maxLength(int $maxLength): static
    {
        $this->maxLength = $maxLength;

        return $this;
    }

    public function autofocus(): static
    {
        $this->autofocus = true;

        return $this;
    }

    protected function componentType(): string
    {
        return 'TranslatableInput';
    }

    protected function componentProps(): array
    {
        return [
            'translatable' => true,
            'placeholder' => $this->placeholder ?: null,
            'readonly' => $this->readonly ?: null,
            'disabled' => $this->disabled ?: null,
            'maxLength' => $this->maxLength,
            'autofocus' => $this->autofocus ?: null,
        ];
    }
}
