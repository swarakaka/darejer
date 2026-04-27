<?php

namespace Darejer\Components;

class InPlaceEditor extends BaseComponent
{
    protected string $displayField = 'name';

    protected string $editField = 'name';

    protected string $cellType = 'text';

    protected array $options = [];

    protected ?string $updateUrl = null;

    protected bool $disabled = false;

    protected ?string $placeholder = null;

    public function field(string $field): static
    {
        $this->displayField = $field;
        $this->editField = $field;

        return $this;
    }

    public function displayField(string $field): static
    {
        $this->displayField = $field;

        return $this;
    }

    public function editField(string $field): static
    {
        $this->editField = $field;

        return $this;
    }

    public function type(string $type): static
    {
        $this->cellType = $type;

        return $this;
    }

    public function select(array $options): static
    {
        $this->cellType = 'select';
        $this->options = $options;

        return $this;
    }

    public function updateUrl(string $url): static
    {
        $this->updateUrl = $url;

        return $this;
    }

    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;

        return $this;
    }

    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    protected function componentType(): string
    {
        return 'InPlaceEditor';
    }

    protected function componentProps(): array
    {
        $normalized = collect($this->options)
            ->map(fn ($label, $value) => ['value' => (string) $value, 'label' => $label])
            ->values()
            ->all();

        return [
            'displayField' => $this->displayField,
            'editField' => $this->editField,
            'cellType' => $this->cellType,
            'options' => $normalized ?: null,
            'updateUrl' => $this->updateUrl,
            'disabled' => $this->disabled ?: null,
            'placeholder' => $this->placeholder,
        ];
    }
}
