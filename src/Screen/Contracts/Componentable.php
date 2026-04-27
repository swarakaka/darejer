<?php

namespace Darejer\Screen\Contracts;

interface Componentable
{
    /**
     * Serialize the component to a plain array for Inertia props.
     * Returns null if the component should be hidden (canSee check failed).
     */
    public function toArray(): ?array;
}
