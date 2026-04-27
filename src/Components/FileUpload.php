<?php

namespace Darejer\Components;

class FileUpload extends BaseComponent
{
    protected bool $multiple = false;

    protected array $accept = [];

    protected ?int $maxSize = null;

    protected ?int $maxFiles = null;

    protected bool $image = false;

    protected string $disk = 'public';

    protected string $path = 'uploads';

    protected bool $preview = true;

    protected bool $disabled = false;

    public function multiple(bool $multiple = true): static
    {
        $this->multiple = $multiple;

        return $this;
    }

    public function accept(array $types): static
    {
        $this->accept = $types;

        return $this;
    }

    public function image(): static
    {
        $this->image = true;
        $this->accept = ['image/*'];
        $this->preview = true;

        return $this;
    }

    public function maxSize(int $kb): static
    {
        $this->maxSize = $kb;

        return $this;
    }

    public function maxFiles(int $max): static
    {
        $this->maxFiles = $max;
        $this->multiple = true;

        return $this;
    }

    public function disk(string $disk): static
    {
        $this->disk = $disk;

        return $this;
    }

    public function path(string $path): static
    {
        $this->path = $path;

        return $this;
    }

    public function noPreview(): static
    {
        $this->preview = false;

        return $this;
    }

    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;

        return $this;
    }

    protected function componentType(): string
    {
        return 'FileUpload';
    }

    protected function componentProps(): array
    {
        return [
            'multiple' => $this->multiple ?: null,
            'accept' => $this->accept ?: null,
            'maxSize' => $this->maxSize,
            'maxFiles' => $this->maxFiles,
            'image' => $this->image ?: null,
            'disk' => $this->disk,
            'path' => $this->path,
            'preview' => $this->preview,
            'disabled' => $this->disabled ?: null,
        ];
    }
}
