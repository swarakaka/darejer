<?php

namespace Darejer\Components;

class Toggle extends BaseComponent
{
    protected ?string $onLabel = null;

    protected ?string $offLabel = null;

    protected bool $disabled = false;

    public function onLabel(string $label): static
    {
        $this->onLabel = $label;

        return $this;
    }

    public function offLabel(string $label): static
    {
        $this->offLabel = $label;

        return $this;
    }

    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;

        return $this;
    }

    protected function componentType(): string
    {
        return 'Toggle';
    }

    protected function componentProps(): array
    {
        return [
            'onLabel' => $this->onLabel,
            'offLabel' => $this->offLabel,
            'disabled' => $this->disabled ?: null,
        ];
    }
}
