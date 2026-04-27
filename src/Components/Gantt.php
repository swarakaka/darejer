<?php

namespace Darejer\Components;

class Gantt extends BaseComponent
{
    protected ?string $dataUrl = null;

    protected int $height = 500;

    protected bool $readonly = false;

    protected string $scale = 'week';

    protected bool $critical = false;

    protected bool $progress = true;

    protected ?string $startDate = null;

    protected ?string $endDate = null;

    protected string $textField = 'text';

    protected string $startField = 'start_date';

    protected string $endField = 'end_date';

    protected string $durationField = 'duration';

    protected string $progressField = 'progress';

    public function dataUrl(string $url): static
    {
        $this->dataUrl = $url;

        return $this;
    }

    public function height(int $px): static
    {
        $this->height = $px;

        return $this;
    }

    public function readonly(bool $readonly = true): static
    {
        $this->readonly = $readonly;

        return $this;
    }

    public function scale(string $scale): static
    {
        $this->scale = $scale;

        return $this;
    }

    public function showCriticalPath(): static
    {
        $this->critical = true;

        return $this;
    }

    public function noProgress(): static
    {
        $this->progress = false;

        return $this;
    }

    public function startDate(string $date): static
    {
        $this->startDate = $date;

        return $this;
    }

    public function endDate(string $date): static
    {
        $this->endDate = $date;

        return $this;
    }

    public function textField(string $field): static
    {
        $this->textField = $field;

        return $this;
    }

    public function startField(string $field): static
    {
        $this->startField = $field;

        return $this;
    }

    public function endField(string $field): static
    {
        $this->endField = $field;

        return $this;
    }

    protected function componentType(): string
    {
        return 'Gantt';
    }

    protected function componentProps(): array
    {
        return [
            'dataUrl' => $this->dataUrl,
            'height' => $this->height,
            'readonly' => $this->readonly ?: null,
            'scale' => $this->scale,
            'critical' => $this->critical ?: null,
            'progress' => $this->progress,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'textField' => $this->textField,
            'startField' => $this->startField,
            'endField' => $this->endField,
            'durationField' => $this->durationField,
            'progressField' => $this->progressField,
        ];
    }
}
