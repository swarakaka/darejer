<?php

namespace Darejer\Navigation;

class NavigationManager
{
    /** @var NavItem[] */
    protected static array $items = [];

    /** @var (\Closure(): NavItem[])|null */
    protected static ?\Closure $resolver = null;

    /**
     * Define the navigation items.
     *
     * Accepts either an array of NavItems or a Closure returning one. Use the
     * Closure form when items reference named routes — eager `route()` calls
     * inside a service provider boot crash under cached routes (the cached
     * route file is `require`d in a booted callback that fires *after*
     * provider boot, so route names aren't resolvable yet). A Closure defers
     * resolution to first `toArray()` call, which always happens during
     * request handling.
     *
     * @param  NavItem[]|\Closure(): NavItem[]  $items
     */
    public static function define(array|\Closure $items): void
    {
        if ($items instanceof \Closure) {
            static::$resolver = $items;
            static::$items = [];

            return;
        }

        static::$resolver = null;
        static::$items = $items;
    }

    public static function add(NavItem $item): void
    {
        static::$items[] = $item;
    }

    public static function flush(): void
    {
        static::$items = [];
        static::$resolver = null;
    }

    /**
     * Returns the serialized navigation for Inertia shared props. Labels and
     * group headings are translated against the active locale at this point —
     * deferring it here (rather than at definition time in a service provider)
     * lets `__()` resolve after the locale middleware has run.
     */
    public static function toArray(): array
    {
        if (static::$resolver !== null) {
            static::$items = (static::$resolver)();
            static::$resolver = null;
        }

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
