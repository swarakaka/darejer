<?php

namespace Darejer\Navigation;

class NavigationManager
{
    /** @var NavItem[] */
    protected static array $items = [];

    /**
     * Define the navigation items.
     *
     * @param  NavItem[]  $items
     */
    public static function define(array $items): void
    {
        static::$items = $items;
    }

    public static function add(NavItem $item): void
    {
        static::$items[] = $item;
    }

    public static function flush(): void
    {
        static::$items = [];
    }

    /**
     * Returns the serialized navigation for Inertia shared props. Labels and
     * group headings are translated against the active locale at this point —
     * deferring it here (rather than at definition time in a service provider)
     * lets `__()` resolve after the locale middleware has run.
     */
    public static function toArray(): array
    {
        return collect(static::$items)
            ->filter(fn (NavItem $item) => $item->isVisible())
            ->map(fn (NavItem $item) => static::translate($item->toArray()))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $node
     * @return array<string, mixed>
     */
    protected static function translate(array $node): array
    {
        if (isset($node['label'])) {
            $node['label'] = __($node['label']);
        }

        if (isset($node['group'])) {
            $node['group'] = __($node['group']);
        }

        if (isset($node['children']) && is_array($node['children'])) {
            $node['children'] = array_map([static::class, 'translate'], $node['children']);
        }

        return $node;
    }
}
