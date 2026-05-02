<?php

namespace Darejer\Screen\Concerns;

use Darejer\Screen\Contracts\Componentable;

trait HasComponents
{
    /** @var Componentable[] */
    protected array $components = [];

    /**
     * @param  Componentable[]  $components
     */
    public function components(array $components): static
    {
        $this->components = $components;

        return $this;
    }

    /**
     * Serialize all components, stripping any that return null (hidden/unauthorized)
     * and unwrapping PermissionGuard wrappers into the flat list.
     *
     * Injects the parent record so closure-based `visible()` checks receive it.
     */
    protected function serializeComponents(): array
    {
        $result = [];
        $record = method_exists($this, 'getRecord') ? $this->getRecord() : null;

        foreach ($this->components as $component) {
            if (method_exists($component, 'withVisibilityRecord')) {
                $component->withVisibilityRecord($record);
            }

            $arr = $component->toArray();

            if ($arr === null) {
                continue;
            }

            // PermissionGuard — unwrap its children into the flat list.
            if (($arr['type'] ?? '') === '__guard__') {
                foreach ($arr['components'] ?? [] as $child) {
                    $result[] = $child;
                }

                continue;
            }

            $result[] = $arr;
        }

        return array_values($result);
    }
}
