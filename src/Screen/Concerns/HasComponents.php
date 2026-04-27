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
     */
    protected function serializeComponents(): array
    {
        $result = [];

        foreach ($this->components as $component) {
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
