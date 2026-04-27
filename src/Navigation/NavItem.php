<?php

namespace Darejer\Navigation;

use Closure;
use Illuminate\Support\Facades\Gate;

class NavItem
{
    protected string $label;

    protected ?string $icon = null;

    protected ?string $url = null;

    protected ?string $route = null;

    protected array $routeParams = [];

    protected array $children = [];

    protected ?string $group = null;

    protected mixed $canSee = null;

    protected ?string $badge = null;

    protected string $badgeColor = 'info';

    protected function __construct(string $label)
    {
        $this->label = $label;
    }

    public static function make(string $label): static
    {
        return new static($label);
    }

    public function icon(string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function url(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function route(string $name, mixed ...$params): static
    {
        // Defer the URL resolution until toArray() — the host app's service
        // provider may register NavItems before package routes are loaded.
        $this->route = $name;
        $this->routeParams = $params;

        return $this;
    }

    protected function resolvedUrl(): ?string
    {
        if ($this->url !== null) {
            return $this->url;
        }
        if ($this->route === null) {
            return null;
        }

        try {
            return route($this->route, $this->routeParams);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  NavItem[]  $children
     */
    public function children(array $children): static
    {
        $this->children = $children;

        return $this;
    }

    public function group(string $label): static
    {
        $this->group = $label;

        return $this;
    }

    public function canSee(string|Closure $permission): static
    {
        $this->canSee = $permission;

        return $this;
    }

    public function badge(string $text, string $color = 'info'): static
    {
        $this->badge = $text;
        $this->badgeColor = $color;

        return $this;
    }

    public function isVisible(): bool
    {
        if ($this->canSee === null) {
            return true;
        }

        if ($this->canSee instanceof Closure) {
            return (bool) ($this->canSee)();
        }

        return Gate::allows($this->canSee)
            || (auth()->check() && auth()->user()->can($this->canSee));
    }

    public function toArray(): array
    {
        if (! $this->isVisible()) {
            return [];
        }

        return array_filter([
            'label' => $this->label,
            'icon' => $this->icon,
            'url' => $this->resolvedUrl(),
            'route' => $this->route,
            'group' => $this->group,
            'badge' => $this->badge,
            'badgeColor' => $this->badge ? $this->badgeColor : null,
            'children' => collect($this->children)
                ->filter(fn (NavItem $c) => $c->isVisible())
                ->map(fn (NavItem $c) => $c->toArray())
                ->filter()
                ->values()
                ->all() ?: null,
        ], fn ($v) => $v !== null && $v !== [] && $v !== false);
    }
}
