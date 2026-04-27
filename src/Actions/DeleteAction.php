<?php

namespace Darejer\Actions;

class DeleteAction extends BaseAction
{
    public function __construct(string $label = 'Delete')
    {
        parent::__construct($label);
        $this->method = 'DELETE';
        $this->variant = 'destructive';
        $this->icon = 'Trash2';
        $this->confirm = 'Are you sure you want to delete this? This action cannot be undone.';
    }

    public static function make(string $label = 'Delete'): static
    {
        return new static($label);
    }

    protected function actionType(): string
    {
        return 'Delete';
    }
}
