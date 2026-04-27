<?php

namespace Darejer\Components;

class TagsInput extends BaseComponent
{
    protected array $suggestions = [];

    protected bool $freeform = true;

    protected ?int $max = null;

    protected string $delimiter = ',';

    protected ?string $placeholder = null;

    protected bool $disabled = false;

    /**
     * @param  string[]  $suggestions
     */
    public function suggestions(array $suggestions): static
    {
        $this->suggestions = $suggestions;

        return $this;
    }

    public function strict(): static
    {
        $this->freeform = false;

        return $this;
    }

    public function max(int $max): static
    {
        $this->max = $max;

        return $this;
    }

    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;

        return $this;
    }

    protected function componentType(): string
    {
        return 'TagsInput';
    }

    protected function componentProps(): array
    {
        return [
            'suggestions' => $this->suggestions ?: null,
            'freeform' => $this->freeform,
            'max' => $this->max,
            'placeholder' => $this->placeholder,
            'disabled' => $this->disabled ?: null,
        ];
    }
}
