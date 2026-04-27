<?php

namespace Darejer\Components;

class DatePicker extends BaseComponent
{
    protected ?string $minDate = null;

    protected ?string $maxDate = null;

    protected bool $range = false;

    protected bool $disabled = false;

    protected string $format = 'Y-m-d';

    protected ?string $placeholder = null;

    protected array $disabledDates = [];

    public function minDate(string $date): static
    {
        $this->minDate = $date;

        return $this;
    }

    public function maxDate(string $date): static
    {
        $this->maxDate = $date;

        return $this;
    }

    public function range(): static
    {
        $this->range = true;

        return $this;
    }

    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;

        return $this;
    }

    public function format(string $format): static
    {
        $this->format = $format;

        return $this;
    }

    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    /**
     * @param  string[]  $dates
     */
    public function disabledDates(array $dates): static
    {
        $this->disabledDates = $dates;

        return $this;
    }

    protected function componentType(): string
    {
        return 'DatePicker';
    }

    protected function componentProps(): array
    {
        return [
            'minDate' => $this->minDate,
            'maxDate' => $this->maxDate,
            'range' => $this->range ?: null,
            'disabled' => $this->disabled ?: null,
            'format' => $this->format,
            'placeholder' => $this->placeholder,
            'disabledDates' => $this->disabledDates ?: null,
        ];
    }
}
