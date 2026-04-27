<?php

namespace Darejer\Components;

class PDFViewer extends BaseComponent
{
    protected ?string $src = null;

    protected ?string $srcField = null;

    protected int $height = 600;

    protected bool $showToolbar = true;

    protected bool $download = true;

    protected bool $disabled = false;

    public function src(string $src): static
    {
        $this->src = $src;

        return $this;
    }

    /**
     * Read the PDF URL from this field on the record.
     */
    public function srcField(string $field): static
    {
        $this->srcField = $field;

        return $this;
    }

    public function height(int $px): static
    {
        $this->height = $px;

        return $this;
    }

    public function noToolbar(): static
    {
        $this->showToolbar = false;

        return $this;
    }

    public function noDownload(): static
    {
        $this->download = false;

        return $this;
    }

    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;

        return $this;
    }

    protected function componentType(): string
    {
        return 'PDFViewer';
    }

    protected function componentProps(): array
    {
        return [
            'src' => $this->src,
            'srcField' => $this->srcField,
            'height' => $this->height,
            'showToolbar' => $this->showToolbar,
            'download' => $this->download,
            'disabled' => $this->disabled ?: null,
        ];
    }
}
