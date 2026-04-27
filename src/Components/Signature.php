<?php

namespace Darejer\Components;

class Signature extends BaseComponent
{
    protected int $height = 160;

    protected string $penColor = '#000000';

    protected string $bgColor = '#ffffff';

    protected bool $disabled = false;

    protected bool $showGuide = true;

    public function height(int $px): static
    {
        $this->height = $px;

        return $this;
    }

    public function penColor(string $color): static
    {
        $this->penColor = $color;

        return $this;
    }

    public function backgroundColor(string $color): static
    {
        $this->bgColor = $color;

        return $this;
    }

    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;

        return $this;
    }

    public function noGuide(): static
    {
        $this->showGuide = false;

        return $this;
    }

    protected function componentType(): string
    {
        return 'Signature';
    }

    protected function componentProps(): array
    {
        return [
            'height' => $this->height,
            'penColor' => $this->penColor,
            'bgColor' => $this->bgColor,
            'disabled' => $this->disabled ?: null,
            'showGuide' => $this->showGuide,
        ];
    }
}
