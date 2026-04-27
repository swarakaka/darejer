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
     */
    protected function serializeActions(): array
    {
        return collect($this->actions)
            ->map(fn (Actionable|Componentable $a) => $a->toArray())
            ->filter()
            ->values()
            ->all();
    }
}
