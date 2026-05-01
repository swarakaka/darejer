# Actions

An **Action** is a button-shaped command in a Screen's toolbar (or in a row, dropdown, dialog, etc.). They live under `Darejer\Actions\*` and serialize into the `actions` array of an Inertia page.

## The shared API

Every action extends `Darejer\Actions\BaseAction` and inherits this fluent API:

| Method | Purpose |
|---|---|
| `make($label)` | Static factory. |
| `label($text)` | Override the label. |
| `url($url)` | Target URL. |
| `method($verb)` | HTTP verb. Defaults vary per action subtype. |
| `dialog()` | Open the URL as a dialog instead of navigating. |
| `icon($name)` | Lucide icon name. |
| `confirm($message)` | Show a confirmation prompt before activating. |
| `variant($variant)` | `'default' \| 'destructive' \| 'outline' \| 'ghost' \| 'secondary' \| 'link'`. |
| `disabled($flag = true)` | |
| `tooltip($text)` | Tooltip on hover. |
| `fullWidth()` | Stretch the action to the full width of its container. |
| `dependOn($field, $value, $operator = 'eq')` | Show only when `$field` matches. |
| `dependOnNotEmpty($field)` | Show when `$field` is non-empty. |
| `dependOnIn($field, $values)` | Show when in. |
| `dependOnConditions($conds, $logic = 'and')` | Combined rule. |
| `canSee($permission\|Closure)` | Strip from the payload when the check fails. Super-admin bypasses. |

When `canSee()` denies, the action returns `null` from `toArray()` and `Screen` strips it before serializing.

---

## Index

| Action | Default verb | Default variant | Default icon | Notes |
|---|---|---|---|---|
| [`ButtonAction`](button.md) | `GET` | `outline` | — | Generic button. |
| [`LinkAction`](link.md) | `GET` | `default` | — | Hyperlink — supports `external()`. |
| [`SaveAction`](save.md) | `POST` | `default` | `Save` | Save button — wired to the Form/Screen save URL. |
| [`DeleteAction`](delete.md) | `DELETE` | `destructive` | `Trash2` | Pre-set delete with confirm prompt. |
| [`CancelAction`](cancel.md) | `GET` | `outline` | `X` | Navigates away without saving. |
| [`DropdownAction`](dropdown.md) | — | `outline` | `ChevronDown` | Wraps multiple actions in a dropdown. |
| [`BulkAction`](bulk-action.md) | `POST` | `outline` | — | Operates on selected rows of a `DataGrid`. |
| [`ModalToggleAction`](modal-toggle.md) | `GET` | `outline` | — | Opens a URL as a modal. |

---

## Usage on a Screen

```php
Screen::make('Edit Product')
    ->record($product)
    ->components([/* … */])
    ->actions([
        Actions\SaveAction::make()
            ->url(route('products.update', $product))
            ->method('PUT'),

        Actions\DeleteAction::make()
            ->url(route('products.destroy', $product))
            ->canSee('products.delete'),

        Actions\CancelAction::make()
            ->url(route('products.index')),
    ])
    ->render();
```

---

## Adding a new action

See [`CONTRIBUTING.md`](../CONTRIBUTING.md). Briefly:

1. Create `src/Actions/MyAction.php` extending `BaseAction`.
2. Implement `actionType()` → string. Optionally `actionProps()` → array for extra fields.
3. Set defaults (variant, method, icon) in the constructor.
4. Build the matching frontend handler in `resources/js/components/darejer/DarejerActions.vue`.
5. Create `docs/actions/<kebab>.md`.
6. Add the row to this index.
7. Update `CHANGELOG.md`.
