<?php

namespace Darejer\Components;

class KeyValueEditor extends BaseComponent
{
    protected string $keyLabel = 'Key';

    protected string $valueLabel = 'Value';

    protected bool $disabled = false;

    protected ?int $max = null;

    protected bool $sortable = false;

    public function keyLabel(string $label): static
    {
        $this->keyLabel = $label;

        return $this;
    }

    public function valueLabel(string $label): static
    {
        $this->valueLabel = $label;

        return $this;
    }

    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;

        return $this;
    }

    public function max(int $max): static
    {
        $this->max = $max;

        return $this;
    }

    public function sortable(): static
    {
        $this->sortable = true;

        return $this;
    }

    protected function componentType(): string
    {
        return 'KeyValueEditor';
    }

    protected function componentProps(): array
    {
        return [
            'keyLabel' => $this->keyLabel,
            'valueLabel' => $this->valueLabel,
            'disabled' => $this->disabled ?: null,
            'max' => $this->max,
            'sortable' => $this->sortable ?: null,
        ];
    }
}
