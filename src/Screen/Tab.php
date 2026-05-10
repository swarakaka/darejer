<?php

namespace Darejer\Screen;

class Tab
{
    /** @var string[] */
    protected array $components = [];

    /** @var array<string, mixed>|null */
    protected ?array $dependOn = null;

    protected ?string $name = null;

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
     * Stable, locale-independent identifier for this tab. The JS side uses
     * it as the TabsTrigger value and the localStorage persistence key, so
     * the active tab survives a locale switch (the translated `title` does
     * not). When unset, a value is auto-derived from the components list.
     */
    public function name(string $name): static
    {
        $this->name = $name;

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
            'name' => $this->name ?? $this->autoName(),
            'title' => $this->title,
            'components' => $this->components,
            'dependOn' => $this->dependOn,
        ], fn ($v) => $v !== null);
    }

    /**
     * Derive a stable identifier from the components list when the caller
     * hasn't set one. Component names are language-independent identifiers,
     * so the result stays the same when the title is translated.
     */
    protected function autoName(): string
    {
        if ($this->components !== []) {
            return implode(',', $this->components);
        }

        return md5($this->title);
    }
}
