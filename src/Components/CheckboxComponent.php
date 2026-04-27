<?php

namespace Darejer\Components;

class CheckboxComponent extends BaseComponent
{
    protected ?string $checkboxLabel = null;

    protected bool $disabled = false;

    public function checkboxLabel(string $label): static
    {
        $this->checkboxLabel = $label;

        return $this;
    }

    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;

        return $this;
    }

    protected function componentType(): string
    {
        return 'Checkbox';
    }

    protected function componentProps(): array
    {
        return [
            'checkboxLabel' => $this->checkboxLabel,
            'disabled' => $this->disabled ?: null,
        ];
    }
}
