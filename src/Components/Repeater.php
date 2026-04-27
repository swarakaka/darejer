<?php

namespace Darejer\Components;

use Darejer\Screen\Contracts\Componentable;

class Repeater extends BaseComponent
{
    /** @var Componentable[] */
    protected array $subComponents = [];

    protected bool $addable = true;

    protected bool $deletable = true;

    protected bool $sortable = false;

    protected bool $collapsed = false;

    protected ?int $maxItems = null;

    protected ?int $minItems = null;

    protected string $addLabel = 'Add item';

    protected string $itemLabel = 'Item';

    protected ?string $itemLabelField = null;

    protected array $defaultItem = [];

    /**
     * @param  Componentable[]  $components
     */
    public function schema(array $components): static
    {
        $this->subComponents = $components;

        return $this;
    }

    public function addable(bool $addable = true): static
    {
        $this->addable = $addable;

        return $this;
    }

    public function deletable(bool $deletable = true): static
    {
        $this->deletable = $deletable;

        return $this;
    }

    public function sortable(bool $sortable = true): static
    {
        $this->sortable = $sortable;

        return $this;
    }

    public function collapsed(bool $collapsed = true): static
    {
        $this->collapsed = $collapsed;

        return $this;
    }

    public function maxItems(int $max): static
    {
        $this->maxItems = $max;

        return $this;
    }

    public function minItems(int $min): static
    {
        $this->minItems = $min;

        return $this;
    }

    public function addLabel(string $label): static
    {
        $this->addLabel = $label;

        return $this;
    }

    public function itemLabel(string $label): static
    {
        $this->itemLabel = $label;

        return $this;
    }

    public function itemLabelField(string $field): static
    {
        $this->itemLabelField = $field;

        return $this;
    }

    public function defaultItem(array $defaults): static
    {
        $this->defaultItem = $defaults;

        return $this;
    }

    protected function componentType(): string
    {
        return 'Repeater';
    }

    protected function componentProps(): array
    {
        $schema = collect($this->subComponents)
            ->map(fn (Componentable $c) => $c->toArray())
            ->filter()
            ->values()
            ->all();

        return [
            'schema' => $schema,
            'addable' => $this->addable,
            'deletable' => $this->deletable,
            'sortable' => $this->sortable ?: null,
            'collapsed' => $this->collapsed ?: null,
            'maxItems' => $this->maxItems,
            'minItems' => $this->minItems,
            'addLabel' => $this->addLabel,
            'itemLabel' => $this->itemLabel,
            'itemLabelField' => $this->itemLabelField,
            'defaultItem' => $this->defaultItem ?: null,
        ];
    }
}
