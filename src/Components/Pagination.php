<?php

namespace Darejer\Components;

class Pagination extends BaseComponent
{
    protected string $dataKey = 'data';

    public function dataKey(string $key): static
    {
        $this->dataKey = $key;

        return $this;
    }

    protected function componentType(): string
    {
        return 'Pagination';
    }

    protected function componentProps(): array
    {
        return [
            'dataKey' => $this->dataKey,
        ];
    }
}
