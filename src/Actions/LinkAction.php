<?php

namespace Darejer\Actions;

class LinkAction extends BaseAction
{
    protected bool $external = false;

    public function __construct(string $label = '')
    {
        parent::__construct($label);
        $this->method = 'GET';
    }

    public static function make(string $label = ''): static
    {
        return new static($label);
    }

    public function external(bool $external = true): static
    {
        $this->external = $external;

        return $this;
    }

    protected function actionType(): string
    {
        return 'Link';
    }

    protected function actionProps(): array
    {
        return [
            'external' => $this->external ?: null,
        ];
    }
}
