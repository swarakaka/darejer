<?php

namespace Darejer\Actions;

class ModalToggleAction extends BaseAction
{
    protected string $modalSize = 'md';

    public function __construct(string $label = '')
    {
        parent::__construct($label);
        $this->method = 'GET';
        $this->variant = 'outline';
        $this->dialog = true;
    }

    public static function make(string $label = ''): static
    {
        return new static($label);
    }

    public function size(string $size): static
    {
        $this->modalSize = $size;

        return $this;
    }

    protected function actionType(): string
    {
        return 'ModalToggle';
    }

    protected function actionProps(): array
    {
        return [
            'modalSize' => $this->modalSize,
        ];
    }
}
