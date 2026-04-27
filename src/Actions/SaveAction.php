<?php

namespace Darejer\Actions;

class SaveAction extends BaseAction
{
    public function __construct(string $label = 'Save')
    {
        parent::__construct($label);
        $this->method = 'POST';
        $this->variant = 'default';
        $this->icon = 'Save';
    }

    public static function make(string $label = 'Save'): static
    {
        return new static($label);
    }

    protected function actionType(): string
    {
        return 'Save';
    }
}
