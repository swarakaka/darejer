<?php

namespace Darejer\Screen\Concerns;

trait HasDialog
{
    protected bool $isDialog = false;

    protected ?string $dialogSize = 'md';

    /**
     * Render this screen as a dialog/modal overlay.
     *
     * @param  string  $size  xs | sm | md | lg | xl | full
     */
    public function dialog(string $size = 'md'): static
    {
        $this->isDialog = true;
        $this->dialogSize = $size;

        return $this;
    }

    public function isDialog(): bool
    {
        return $this->isDialog;
    }
}
