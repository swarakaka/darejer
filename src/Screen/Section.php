<?php

namespace Darejer\Screen;

class Section
{
    /** @var string[] */
    protected array $components = [];

    protected bool $collapsed = false;

    protected bool $collapsible = false;

    /** @var array<string, mixed>|null */
    protected ?array $dependOn = null;

    protected ?string $title = null;

    protected function __construct(protected string $key) {}

    public static function make(string $key): static
    {
        return new static($key);
    }

    public function title(?string $title): static
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

    public function collapsible(bool $collapsible = true): static
    {
        $this->collapsible = $collapsible;

        if (! $collapsible) {
            $this->collapsed = false;
        }

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
            'key' => $this->key,
            'title' => $this->title,
            'components' => $this->components,
            'collapsed' => $this->collapsed,
            'collapsible' => $this->collapsible,
            'dependOn' => $this->dependOn,
        ], fn ($v) => $v !== null);
    }
}
