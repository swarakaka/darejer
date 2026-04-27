<?php

namespace Darejer\Screen\Contracts;

interface Actionable
{
    /**
     * Serialize the action to a plain array for Inertia props.
     * Returns null if the action should be hidden.
     */
    public function toArray(): ?array;
}
