<?php

namespace Darejer\Components;

use Darejer\DataGrid\Filter;

class FilterPanel extends BaseComponent
{
    /** @var Filter[] */
    protected array $filters = [];

    protected string $target = '';

    protected string $layout = 'bar';

    protected bool $collapsed = false;

    /**
     * @param  Filter[]  $filters
     */
    public function filters(array $filters): static
    {
        $this->filters = $filters;

        return $this;
    }

    public function controls(string $componentName): static
    {
        $this->target = $componentName;

        return $this;
    }

    public function bar(): static
    {
        $this->layout = 'bar';

        return $this;
    }

    public function sidebar(): static
    {
        $this->layout = 'sidebar';

        return $this;
    }

    public function collapsed(bool $collapsed = true): static
    {
        $this->collapsed = $collapsed;

        return $this;
    }

    protected function componentType(): string
    {
        return 'FilterPanel';
    }

    protected function componentProps(): array
    {
        return [
            'filters' => array_map(fn (Filter $f) => $f->toArray(), $this->filters),
            'target' => $this->target,
            'layout' => $this->layout,
            'collapsed' => $this->collapsed ?: null,
        ];
    }
}
