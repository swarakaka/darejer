<?php

namespace Darejer\Components;

class Diagram extends BaseComponent
{
    protected array $nodes = [];

    protected array $edges = [];

    protected bool $readonly = false;

    protected int $height = 400;

    protected bool $minimap = true;

    protected bool $controls = true;

    protected bool $background = true;

    protected string $direction = 'LR';

    protected ?string $dataUrl = null;

    public function nodes(array $nodes): static
    {
        $this->nodes = $nodes;

        return $this;
    }

    public function edges(array $edges): static
    {
        $this->edges = $edges;

        return $this;
    }

    public function dataUrl(string $url): static
    {
        $this->dataUrl = $url;

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

    public function noMinimap(): static
    {
        $this->minimap = false;

        return $this;
    }

    public function noControls(): static
    {
        $this->controls = false;

        return $this;
    }

    public function noBackground(): static
    {
        $this->background = false;

        return $this;
    }

    public function direction(string $direction): static
    {
        $this->direction = strtoupper($direction);

        return $this;
    }

    protected function componentType(): string
    {
        return 'Diagram';
    }

    protected function componentProps(): array
    {
        return [
            'nodes' => $this->nodes ?: null,
            'edges' => $this->edges ?: null,
            'dataUrl' => $this->dataUrl,
            'readonly' => $this->readonly ?: null,
            'height' => $this->height,
            'minimap' => $this->minimap,
            'controls' => $this->controls,
            'background' => $this->background,
            'direction' => $this->direction,
        ];
    }
}
