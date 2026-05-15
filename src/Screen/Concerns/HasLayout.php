<?php

namespace Darejer\Screen\Concerns;

trait HasLayout
{
    protected ?string $layout = null;

    /**
     * Select which Inertia layout wraps this screen. Layout names map to
     * Vue components in the package's layout registry (e.g. 'app', 'minimal').
     * Leave unset for the default 'app' layout.
     */
    public function layout(?string $layout): static
    {
        $this->layout = $layout;

        return $this;
    }

    public function getLayout(): ?string
    {
        return $this->layout;
    }
}
