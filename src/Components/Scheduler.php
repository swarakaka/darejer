<?php

namespace Darejer\Components;

class Scheduler extends BaseComponent
{
    protected ?string $dataUrl = null;

    protected string $initialView = 'dayGridMonth';

    protected bool $readonly = false;

    protected int $height = 500;

    protected ?string $editUrl = null;

    protected ?string $createUrl = null;

    protected bool $editDialog = false;

    protected bool $createDialog = false;

    protected string $titleField = 'title';

    protected string $startField = 'start';

    protected string $endField = 'end';

    protected ?string $colorField = null;

    protected ?string $defaultColor = null;

    protected array $views = ['dayGridMonth', 'timeGridWeek', 'timeGridDay', 'listWeek'];

    public function dataUrl(string $url): static
    {
        $this->dataUrl = $url;

        return $this;
    }

    public function model(string $modelClass): static
    {
        $slug = strtolower(class_basename($modelClass));
        $this->dataUrl = route('darejer.data.index', ['model' => $slug, 'per_page' => 500]);

        return $this;
    }

    public function initialView(string $view): static
    {
        $this->initialView = $view;

        return $this;
    }

    public function readonly(bool $readonly = true): static
    {
        $this->readonly = $readonly;

        return $this;
    }

    public function height(int $px): static
    {
        $this->height = $px;

        return $this;
    }

    public function editUrl(string $url, bool $dialog = false): static
    {
        $this->editUrl = $url;
        $this->editDialog = $dialog;

        return $this;
    }

    public function createUrl(string $url, bool $dialog = false): static
    {
        $this->createUrl = $url;
        $this->createDialog = $dialog;

        return $this;
    }

    public function titleField(string $field): static
    {
        $this->titleField = $field;

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

    public function colorField(string $field): static
    {
        $this->colorField = $field;

        return $this;
    }

    public function defaultColor(string $color): static
    {
        $this->defaultColor = $color;

        return $this;
    }

    public function views(array $views): static
    {
        $this->views = $views;

        return $this;
    }

    protected function componentType(): string
    {
        return 'Scheduler';
    }

    protected function componentProps(): array
    {
        return [
            'dataUrl' => $this->dataUrl,
            'initialView' => $this->initialView,
            'readonly' => $this->readonly ?: null,
            'height' => $this->height,
            'editUrl' => $this->editUrl,
            'createUrl' => $this->createUrl,
            'editDialog' => $this->editDialog ?: null,
            'createDialog' => $this->createDialog ?: null,
            'titleField' => $this->titleField,
            'startField' => $this->startField,
            'endField' => $this->endField,
            'colorField' => $this->colorField,
            'defaultColor' => $this->defaultColor,
            'views' => $this->views,
        ];
    }
}
