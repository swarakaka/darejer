<?php

namespace Darejer\Components;

class TextInput extends BaseComponent
{
    protected string $placeholder = '';

    protected string $inputType = 'text';

    protected bool $readonly = false;

    protected bool $disabled = false;

    protected ?int $maxLength = null;

    protected ?string $prefix = null;

    protected ?string $suffix = null;

    protected bool $autofocus = false;

    protected bool $revealable = false;

    protected int $decimals = 0;

    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function type(string $type): static
    {
        $this->inputType = $type;

        return $this;
    }

    public function email(): static
    {
        return $this->type('email');
    }

    public function password(): static
    {
        $this->revealable = true;

        return $this->type('password');
    }

    /**
     * Render as a numeric input. `$decimals` fixes display precision — e.g.
     * `number(3)` formats the value to 3 fraction digits on blur and sets
     * `step="0.001"`. Defaults to `0` (integer input, `step="1"`).
     */
    public function number(int $decimals = 0): static
    {
        $this->decimals = $decimals;

        return $this->type('number');
    }

    public function url(): static
    {
        return $this->type('url');
    }

    public function tel(): static
    {
        return $this->type('tel');
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

    public function prefix(string $prefix): static
    {
        $this->prefix = $prefix;

        return $this;
    }

    public function suffix(string $suffix): static
    {
        $this->suffix = $suffix;

        return $this;
    }

    public function autofocus(): static
    {
        $this->autofocus = true;

        return $this;
    }

    public function revealable(bool $revealable = true): static
    {
        $this->revealable = $revealable;

        return $this;
    }

    protected function componentType(): string
    {
        return 'TextInput';
    }

    protected function componentProps(): array
    {
        return [
            'placeholder' => $this->placeholder ?: null,
            'inputType' => $this->inputType,
            'readonly' => $this->readonly ?: null,
            'disabled' => $this->disabled ?: null,
            'maxLength' => $this->maxLength,
            'prefix' => $this->prefix,
            'suffix' => $this->suffix,
            'autofocus' => $this->autofocus ?: null,
            'revealable' => $this->revealable ?: null,
            'decimals' => $this->decimals,
        ];
    }
}
