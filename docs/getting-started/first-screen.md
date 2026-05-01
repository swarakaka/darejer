# Your First Screen

Goal: build a complete CRUD for `Product` — list, create, edit, delete — with **only PHP**.

## 1. Model

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['name', 'sku', 'price', 'category_id', 'is_active'];

    protected $casts = ['price' => 'float', 'is_active' => 'bool'];
}
```

## 2. Controller

Extend `DarejerController`. **No `routes/web.php` entry is needed.**

```php
namespace App\Http\Controllers;

use App\Models\Product;
use Darejer\Actions;
use Darejer\Components;
use Darejer\DataGrid\Column;
use Darejer\DataGrid\RowAction;
use Darejer\Http\Controllers\DarejerController;
use Darejer\Routing\RoutePattern;
use Darejer\Screen\Screen;
use Illuminate\Http\Request;

class ProductController extends DarejerController
{
    protected ?string $resource = 'products';
    protected ?string $model    = Product::class;

    public function index()
    {
        return Screen::make('Products')
            ->components([
                Components\DataGrid::make('products')
                    ->model(Product::class)
                    ->columns([
                        Column::make('sku')->sortable(),
                        Column::make('name')->sortable()->searchable(),
                        Column::make('price')->alignRight(),
                        Column::make('is_active')->boolean(),
                    ])
                    ->rowActions([
                        RowAction::edit(RoutePattern::row('products.edit')),
                        RowAction::delete(RoutePattern::row('products.destroy')),
                    ]),
            ])
            ->actions([
                Actions\LinkAction::make('New product')
                    ->url(route('products.create'))
                    ->icon('Plus')
                    ->variant('default'),
            ])
            ->render();
    }

    public function create()
    {
        return Screen::make('New Product')
            ->record(new Product)
            ->components($this->fields())
            ->actions([
                Actions\SaveAction::make()
                    ->url(route('products.store'))
                    ->method('POST'),
                Actions\CancelAction::make()
                    ->url(route('products.index')),
            ])
            ->render();
    }

    public function edit(Product $product)
    {
        return Screen::make('Edit Product')
            ->record($product)
            ->components($this->fields())
            ->actions([
                Actions\SaveAction::make()
                    ->url(route('products.update', $product))
                    ->method('PUT'),
                Actions\DeleteAction::make()
                    ->url(route('products.destroy', $product)),
                Actions\CancelAction::make()
                    ->url(route('products.index')),
            ])
            ->render();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'sku'         => 'required|string|unique:products,sku',
            'price'       => 'required|numeric|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'is_active'   => 'boolean',
        ]);

        Product::create($data);

        return redirect()->route('products.index')
            ->with('success', 'Product created.');
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'sku'         => 'required|string|unique:products,sku,'.$product->id,
            'price'       => 'required|numeric|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'is_active'   => 'boolean',
        ]);

        $product->update($data);

        return redirect()->route('products.edit', $product)
            ->with('success', 'Product updated.');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('products.index')
            ->with('success', 'Product deleted.');
    }

    protected function fields(): array
    {
        return [
            Components\TextInput::make('sku')->label('SKU')->required(),
            Components\TextInput::make('name')->label('Name')->required(),
            Components\TextInput::make('price')->number()->required(),
            Components\Combobox::make('category_id')
                ->label('Category')
                ->model(\App\Models\Category::class),
            Components\Toggle::make('is_active')->label('Active')->default(true),
        ];
    }
}
```

## 3. That's it

Visit `/products` — you have a working CRUD. Darejer registered every route automatically:

| Method | URI | Route name |
|---|---|---|
| `GET` | `/products` | `products.index` |
| `GET` | `/products/create` | `products.create` |
| `POST` | `/products` | `products.store` |
| `GET` | `/products/{product}/edit` | `products.edit` |
| `PUT|PATCH` | `/products/{product}` | `products.update` |
| `DELETE` | `/products/{product}` | `products.destroy` |

The model parameter `{product}` is bound to `App\Models\Product` via the `protected ?string $model = Product::class` declaration.

## Going further

- Render the create screen as a dialog: add `->dialog()` on the `Screen::make(...)` call.
- Add a custom endpoint: declare `#[Route('POST', 'bulk/archive', name: 'bulk.archive')]` on a controller method. See [`architecture/controller.md`](../architecture/controller.md).
- Add validation-driven inline errors: any 422 with `{ message, errors }` is auto-surfaced. See [`architecture/json-envelope.md`](../architecture/json-envelope.md).
- Add a search bar to the topbar: use the `Darejer\Concerns\Searchable` trait on your model. See [`api-reference/php-api.md`](../api-reference/php-api.md).
