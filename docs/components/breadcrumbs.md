# BreadcrumbsComponent

> Inline breadcrumb trail. Most screens use `Screen::breadcrumbs([...])` instead — this component exists for ad-hoc placements (e.g. inside a `<section>` body).

## Import / usage

```php
use Darejer\Components\BreadcrumbsComponent;

BreadcrumbsComponent::make('crumbs')->crumbs([
    ['label' => 'Home',     'url' => '/'],
    ['label' => 'Products', 'url' => route('products.index')],
    ['label' => 'Edit'],
])
```

## Props

Inherits the shared API from `BaseComponent`.

| Method | Type | Default | Description |
|---|---|---|---|
| `crumbs($crumbs)` | `array` | `[]` | Each crumb: `['label' => '…', 'url' => '…']`. The last crumb usually omits `url`. |

## Slots

None.

## Events

None.

## Examples

### Basic

```php
BreadcrumbsComponent::make('breadcrumbs')->crumbs([
    ['label' => 'Dashboard', 'url' => route('darejer.dashboard')],
    ['label' => 'Customers'],
])
```

### Real-world — pair with `Screen::breadcrumbs`

You almost always want the screen-level breadcrumbs (rendered in the topbar). Use this component only when you need a duplicate trail inside a panel:

```php
Screen::make('Customer')
    ->breadcrumbs([
        ['label' => 'Customers', 'url' => route('customers.index')],
        ['label' => $customer->name],
    ])
    ->components([
        // … fields …
    ])
    ->render();
```

## Accessibility

- Renders shadcn-vue `<Breadcrumb>` — `<nav aria-label="Breadcrumb">` with an ordered list of links.
- The last (current) crumb has `aria-current="page"`.

## Related

- [`architecture/screen-engine.md`](../architecture/screen-engine.md#builder-api) — `Screen::breadcrumbs([...])` for the topbar trail.
- [`Navigation`](navigation.md) — for cross-section navigation.
