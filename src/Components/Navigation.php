<?php

namespace Darejer\Components;

class Navigation extends BaseComponent
{
    protected array $navItems = [];

    protected string $style = 'tabs';

    protected bool $stretch = false;

    /**
     * @param  array  $items  [['label' => 'Details', 'url' => '/products/1', 'icon' => 'FileText', 'active' => true]]
     */
    public function items(array $items): static
    {
        $this->navItems = $items;

        return $this;
    }

    public function tabs(): static
    {
        $this->style = 'tabs';

        return $this;
    }

    public function pills(): static
    {
        $this->style = 'pills';

        return $this;
    }

    public function underline(): static
    {
        $this->style = 'underline';

        return $this;
    }

    public function stretch(): static
    {
        $this->stretch = true;

        return $this;
    }

    protected function componentType(): string
    {
        return 'Navigation';
    }

    protected function componentProps(): array
    {
        return [
            'navItems' => $this->navItems,
            'style' => $this->style,
            'stretch' => $this->stretch ?: null,
        ];
    }
}
