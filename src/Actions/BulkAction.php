<?php

namespace Darejer\Actions;

class BulkAction extends BaseAction
{
    protected ?string $batchUrl = null;

    protected string $batchParam = 'ids';

    public function __construct(string $label = '')
    {
        parent::__construct($label);
        $this->method = 'POST';
        $this->variant = 'outline';
    }

    public static function make(string $label = ''): static
    {
        return new static($label);
    }

    public function batchUrl(string $url): static
    {
        $this->batchUrl = $url;

        return $this;
    }

    public function batchParam(string $param): static
    {
        $this->batchParam = $param;

        return $this;
    }

    protected function actionType(): string
    {
        return 'BulkAction';
    }

    protected function actionProps(): array
    {
        return [
            'batchUrl' => $this->batchUrl,
            'batchParam' => $this->batchParam,
        ];
    }
}
