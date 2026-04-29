<?php

namespace Darejer\Components;

use BackedEnum;
use Darejer\Support\EnumOptions;

class SelectComponent extends BaseComponent
{
    protected array $options = [];

    protected bool $disabled = false;

    protected bool $searchable = false;

    protected ?string $placeholder = null;

    protected bool $multiple = false;

    /**
     * Set selectable options. Accepts either a `[value => label]` array or a
     * backed-enum class string (label resolved via `label()` or static
     * `options()` on the enum).
     *
     * @param  array<string, string>|class-string<BackedEnum>  $options
     */
    public function options(array|string $options): static
    {
        $this->options = EnumOptions::labels($options);

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

    public function searchable(): static
    {
        $this->searchable = true;

        return $this;
    }

    public function multiple(): static
    {
        $this->multiple = true;

        return $this;
    }

    protected function componentType(): string
    {
        return 'Select';
    }

    protected function componentProps(): array
    {
        $normalized = collect($this->options)
            ->map(fn ($label, $value) => ['value' => (string) $value, 'label' => $label])
            ->values()
            ->all();

        return [
            'options' => $normalized,
            'placeholder' => $this->placeholder,
            'disabled' => $this->disabled ?: null,
            'searchable' => $this->searchable ?: null,
            'multiple' => $this->multiple ?: null,
        ];
    }
}
