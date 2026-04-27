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
     * Returns the serialized navigation for Inertia shared props.
     */
    public static function toArray(): array
    {
        return collect(static::$items)
            ->filter(fn (NavItem $item) => $item->isVisible())
            ->map(fn (NavItem $item) => $item->toArray())
            ->filter()
            ->values()
            ->all();
    }
}
