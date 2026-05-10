<?php

namespace Darejer\Actions;

class LinkAction extends BaseAction
{
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
        return $this->newTab($external);
    }

    protected function actionType(): string
    {
        return 'Link';
    }
}
