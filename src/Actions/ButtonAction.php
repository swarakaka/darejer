<?php

namespace Darejer\Actions;

class ButtonAction extends BaseAction
{
    public function __construct(string $label = '')
    {
        parent::__construct($label);
        $this->variant = 'outline';
    }

    public static function make(string $label = ''): static
    {
        return new static($label);
    }

    protected function actionType(): string
    {
        return 'Button';
    }
}
