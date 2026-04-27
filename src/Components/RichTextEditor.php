<?php

namespace Darejer\Components;

class RichTextEditor extends BaseComponent
{
    protected ?string $placeholder = null;

    protected int $minHeight = 200;

    protected int $maxHeight = 0;

    protected bool $disabled = false;

    protected bool $readonly = false;

    protected ?int $maxCharacters = null;

    protected array $toolbar = [];

    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function minHeight(int $px): static
    {
        $this->minHeight = $px;

        return $this;
    }

    public function maxHeight(int $px): static
    {
        $this->maxHeight = $px;

        return $this;
    }

    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;

        return $this;
    }

    public function readonly(bool $readonly = true): static
    {
        $this->readonly = $readonly;

        return $this;
    }

    public function maxCharacters(int $max): static
    {
        $this->maxCharacters = $max;

        return $this;
    }

    /**
     * @param  string[]  $tools
     */
    public function toolbar(array $tools): static
    {
        $this->toolbar = $tools;

        return $this;
    }

    protected function componentType(): string
    {
        return 'RichTextEditor';
    }

    protected function componentProps(): array
    {
        return [
            'placeholder' => $this->placeholder,
            'minHeight' => $this->minHeight,
            'maxHeight' => $this->maxHeight ?: null,
            'disabled' => $this->disabled ?: null,
            'readonly' => $this->readonly ?: null,
            'maxCharacters' => $this->maxCharacters,
            'toolbar' => $this->toolbar ?: null,
        ];
    }
}
