# Navigation

> Inline tab / pill / underlined navigation strip. Use to switch between related screens (e.g. Details / Stock / Pricing for a Product).

## Import / usage

```php
use Darejer\Components\Navigation;

Navigation::make('product_nav')
    ->items([
        ['label' => 'Details',  'url' => route('products.edit', $product), 'active' => true],
        ['label' => 'Stock',    'url' => route('products.stock', $product)],
        ['label' => 'Pricing',  'url' => route('products.pricing', $product), 'icon' => 'DollarSign'],
    ])
```

## Props

Inherits the shared API from `BaseComponent`.

| Method | Type | Default | Description |
|---|---|---|---|
| `items($items)` | `array` | `[]` | Each item: `['label' => '…', 'url' => '…', 'icon' => 'IconName', 'active' => true\|false]`. |
| `tabs()` | — | style `tabs` | Tab-style (default). |
| `pills()` | — | — | Pill-style. |
| `underline()` | — | — | Underlined text style. |
| `stretch()` | — | `false` | Stretch items to fill the container width. |

The `active` flag must be set by the controller — Darejer doesn't auto-detect the active route.

## Slots

None.

## Events

None — items are normal links.

## Examples

### Basic

```php
Navigation::make('settings_nav')
    ->items([
        ['label' => 'Profile',     'url' => route('settings.profile')],
        ['label' => 'Security',    'url' => route('settings.security'), 'active' => true],
        ['label' => 'Integrations','url' => route('settings.integrations')],
    ])
```

### Intermediate — pills, with icons

```php
Navigation::make('billing_nav')
    ->pills()
    ->items([
        ['label' => 'Invoices', 'icon' => 'FileText',   'url' => route('billing.invoices'), 'active' => $section === 'invoices'],
        ['label' => 'Cards',    'icon' => 'CreditCard', 'url' => route('billing.cards'),    'active' => $section === 'cards'],
        ['label' => 'Plan',     'icon' => 'Sparkles',   'url' => route('billing.plan'),     'active' => $section === 'plan'],
    ])
```

### Real-world — building active state

```php
Navigation::make('product_nav')
    ->underline()
    ->stretch()
    ->items(collect([
        'edit'    => ['Details',  route('products.edit', $product)],
        'stock'   => ['Stock',    route('products.stock', $product)],
        'pricing' => ['Pricing',  route('products.pricing', $product)],
    ])->map(fn ($entry, $key) => [
        'label'  => $entry[0],
        'url'    => $entry[1],
        'active' => request()->routeIs("products.$key"),
    ])->values()->all())
```

## Accessibility

- Renders as a `<nav aria-label="…">` with `<ul>` / `<li>` items.
- The active item exposes `aria-current="page"`.

## Related

- [`BreadcrumbsComponent`](breadcrumbs.md) — for hierarchical context.
- See [`navigation` in `NavigationManager`](../api-reference/php-api.md#sidebar-navigation--navigationmanager) for sidebar nav.
