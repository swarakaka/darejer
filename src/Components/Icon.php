<?php

namespace Darejer\Components;

class Icon extends BaseComponent
{
    protected string $iconName = 'Circle';

    protected string $size = 'md';

    protected ?string $color = null;

    protected ?string $title = null;

    public function icon(string $name): static
    {
        $this->iconName = $name;

        return $this;
    }

    public function size(string $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function color(string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function title(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    protected function componentType(): string
    {
        return 'Icon';
    }

    protected function componentProps(): array
    {
        return [
            'iconName' => $this->iconName,
            'size' => $this->size,
            'color' => $this->color,
            'title' => $this->title,
        ];
    }
}
