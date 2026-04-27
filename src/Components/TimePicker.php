<?php

namespace Darejer\Components;

class TimePicker extends BaseComponent
{
    protected bool $seconds = false;

    protected bool $hour12 = false;

    protected bool $disabled = false;

    protected ?string $minTime = null;

    protected ?string $maxTime = null;

    protected ?string $placeholder = null;

    protected int $step = 1;

    public function withSeconds(): static
    {
        $this->seconds = true;

        return $this;
    }

    public function hour12(): static
    {
        $this->hour12 = true;

        return $this;
    }

    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;

        return $this;
    }

    public function minTime(string $time): static
    {
        $this->minTime = $time;

        return $this;
    }

    public function maxTime(string $time): static
    {
        $this->maxTime = $time;

        return $this;
    }

    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function step(int $minutes): static
    {
        $this->step = $minutes;

        return $this;
    }

    protected function componentType(): string
    {
        return 'TimePicker';
    }

    protected function componentProps(): array
    {
        return [
            'seconds' => $this->seconds ?: null,
            'hour12' => $this->hour12 ?: null,
            'disabled' => $this->disabled ?: null,
            'minTime' => $this->minTime,
            'maxTime' => $this->maxTime,
            'placeholder' => $this->placeholder,
            'step' => $this->step !== 1 ? $this->step : null,
        ];
    }
}
