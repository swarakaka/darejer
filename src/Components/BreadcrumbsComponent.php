<?php

namespace Darejer\Components;

class BreadcrumbsComponent extends BaseComponent
{
    protected array $crumbs = [];

    /**
     * @param  array  $crumbs  [['label' => 'Home', 'url' => '/'], ['label' => 'Products']]
     */
    public function crumbs(array $crumbs): static
    {
        $this->crumbs = $crumbs;

        return $this;
    }

    protected function componentType(): string
    {
        return 'BreadcrumbsComponent';
    }

    protected function componentProps(): array
    {
        return [
            'crumbs' => $this->crumbs,
        ];
    }
}
