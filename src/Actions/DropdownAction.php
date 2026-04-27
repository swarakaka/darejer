<?php

namespace Darejer\Actions;

class DropdownAction extends BaseAction
{
    /** @var BaseAction[] */
    protected array $items = [];

    public function __construct(string $label = 'Actions')
    {
        parent::__construct($label);
        $this->variant = 'outline';
        $this->icon = 'ChevronDown';
    }

    public static function make(string $label = 'Actions'): static
    {
        return new static($label);
    }

    /**
     * @param  BaseAction[]  $items
     */
    public function items(array $items): static
    {
        $this->items = $items;

        return $this;
    }

    protected function actionType(): string
    {
        return 'Dropdown';
    }

    protected function actionProps(): array
    {
        return [
            'items' => collect($this->items)
                ->map(fn (BaseAction $a) => $a->toArray())
                ->filter()
                ->values()
                ->all(),
        ];
    }
}
