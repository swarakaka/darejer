<?php

namespace Darejer\Actions;

class CancelAction extends BaseAction
{
    public function __construct(string $label = 'Cancel')
    {
        parent::__construct($label);
        $this->method = 'GET';
        $this->variant = 'outline';
        $this->icon = 'X';
    }

    public static function make(string $label = 'Cancel'): static
    {
        return new static($label);
    }

    protected function actionType(): string
    {
        return 'Cancel';
    }
}
