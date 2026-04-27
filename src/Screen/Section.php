<?php

namespace Darejer\Screen;

class Section
{
    /** @var string[] */
    protected array $components = [];

    protected bool $collapsed = false;

    /** @var array<string, mixed>|null */
    protected ?array $dependOn = null;

    protected function __construct(protected string $title) {}

    public static function make(string $title): static
    {
        return new static($title);
    }

    public function title(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @param  string[]  $components
     */
    public function components(array $components): static
    {
        $this->components = $components;

        return $this;
    }

    public function collapsed(bool $collapsed = true): static
    {
        $this->collapsed = $collapsed;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $rule
     */
    public function dependOn(array $rule): static
    {
        $this->dependOn = $rule;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'title' => $this->title,
            'components' => $this->components,
            'collapsed' => $this->collapsed,
            'dependOn' => $this->dependOn,
        ], fn ($v) => $v !== null);
    }
}
