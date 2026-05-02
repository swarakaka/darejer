<?php

namespace Darejer\Screen\Concerns;

use Darejer\Screen\Contracts\Actionable;
use Darejer\Screen\Contracts\Componentable;

trait HasActions
{
    /** @var Actionable[] */
    protected array $actions = [];

    /**
     * @param  Actionable[]  $actions
     */
    public function actions(array $actions): static
    {
        $this->actions = $actions;

        return $this;
    }

    /**
     * Serialize all actions, stripping any that return null (hidden/unauthorized).
     * Injects the parent record so closure-based `visible()` checks receive it.
     */
    protected function serializeActions(): array
    {
        $record = method_exists($this, 'getRecord') ? $this->getRecord() : null;

        return collect($this->actions)
            ->map(function (Actionable|Componentable $a) use ($record) {
                if (method_exists($a, 'withVisibilityRecord')) {
                    $a->withVisibilityRecord($record);
                }

                return $a->toArray();
            })
            ->filter()
            ->values()
            ->all();
    }
}
