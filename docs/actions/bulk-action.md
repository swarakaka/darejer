# BulkAction

> Operates on the selected rows of a `DataGrid`. The frontend collects checked row IDs and submits them as `ids` (or your chosen `batchParam`) to the `batchUrl`.

Requires the target `DataGrid` to have `->selectable()`.

## Import / usage

```php
use Darejer\Actions\BulkAction;

BulkAction::make('Archive selected')
    ->batchUrl(route('products.bulk.archive'))
    ->icon('Archive')
    ->confirm('Archive selected products?')
```

## Defaults

| Field | Default |
|---|---|
| `variant` | `outline` |
| `method` | `POST` |
| `icon` | — |

## Props

Inherits the [shared action API](README.md#the-shared-api), plus:

| Method | Type | Default | Description |
|---|---|---|---|
| `batchUrl($url)` | `string` | `null` | Endpoint to POST to. |
| `batchParam($param)` | `string` | `'ids'` | Request param name for the array of selected row IDs. |

## Examples

### Basic

```php
BulkAction::make('Delete selected')
    ->batchUrl(route('products.bulk.destroy'))
    ->method('DELETE')
    ->variant('destructive')
    ->confirm('Delete selected products?')
```

### Intermediate — custom payload key

```php
BulkAction::make('Assign category')
    ->batchUrl(route('products.bulk.categorize'))
    ->batchParam('product_ids')
    ->icon('FolderInput')
```

### Real-world — backend handler

Frontend payload:

```json
POST /products/bulk/archive
{ "ids": [1, 2, 3] }
```

Controller:

```php
use Darejer\Routing\Route;

#[Route('POST', 'bulk/archive', name: 'bulk.archive')]
public function bulkArchive(Request $request)
{
    $ids = $request->validate(['ids' => 'array', 'ids.*' => 'integer'])['ids'];

    $count = Product::whereIn('id', $ids)->update(['archived_at' => now()]);

    return $this->jsonSuccess("Archived {$count} products", ['count' => $count]);
}
```

## Accessibility

- Bulk action buttons should be visually distinct from the grid's row checkboxes; place them above the grid (in screen actions) or in a sticky header.

## Related

- [`DataGrid`](../components/data-grid.md) — must enable `->selectable()`.
- [`architecture/json-envelope.md`](../architecture/json-envelope.md) — `jsonSuccess` envelope.
