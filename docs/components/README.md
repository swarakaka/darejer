# Components

Every component is a PHP class under `Darejer\Components\*`. Components serialize into a JSON props blob that the generic `Screen.vue` page paints.

## The shared API

Every component extends `Darejer\Components\BaseComponent` and inherits this fluent API:

| Method | Purpose |
|---|---|
| `make($name)` | Static factory — `$name` is the field key bound to the record. |
| `label($label)` | Field label. |
| `required($flag = true)` | Mark as required (visual + serialized; backend validation is your responsibility). |
| `hint($text)` | Help text below the field. |
| `tooltip($text)` | Tooltip on the label. |
| `default($value)` | Initial value when record is null. |
| `fullWidth($flag = true)` | Make the field span the full form-grid row. |
| `dependOn($field, $value)` | Show only when `$field` equals `$value`. |
| `dependOnNot($field, $value)` | Show when not equal. |
| `dependOnIn($field, $values)` | Show when `$field` value is in `$values`. |
| `dependOnNotIn($field, $values)` | Show when not in. |
| `dependOnEmpty($field)` | Show when value is empty. |
| `dependOnNotEmpty($field)` | Show when value is non-empty. |
| `dependOnConditions($conds, $logic = 'and')` | Combine multiple rules with `and`/`or`. |
| `canSee($permission\|Closure)` | Strip the component from the props when the check fails. Super-admin bypasses. |

Every component is rendered inside a `<FieldWrapper>` that handles label, error message, tooltip, and hint. Components are never naked.

---

## Index

### Inputs
- [`TextInput`](text-input.md) — single-line text, with `text|email|password|number|url|tel` types
- [`Textarea`](textarea.md)
- [`RichTextEditor`](rich-text-editor.md) — TipTap-powered WYSIWYG
- [`Combobox`](combobox.md) — searchable, model-bound, addable
- [`SelectComponent`](select.md) — native shadcn-vue Select
- [`Toggle`](toggle.md)
- [`CheckboxComponent`](checkbox.md)
- [`RadioGroup`](radio-group.md)
- [`DatePicker`](date-picker.md)
- [`TimePicker`](time-picker.md)
- [`TagsInput`](tags-input.md)
- [`KeyValueEditor`](key-value-editor.md)
- [`FileUpload`](file-upload.md) — drag-and-drop
- [`Signature`](signature.md) — signature_pad

### Translatable
- [`TranslatableInput`](translatable-input.md)
- [`TranslatableTextarea`](translatable-textarea.md)

### Display
- [`Display`](display.md) — read-only, value-aware: badges, dates, money, booleans

### Data tables
- [`DataGrid`](data-grid.md) — server-side pagination, sorting, filtering
- [`TreeGrid`](tree-grid.md) — hierarchical
- [`EditableTable`](editable-table.md) — line-item rows
- [`InPlaceEditor`](in-place-editor.md) — click-to-edit cells
- [`Repeater`](repeater.md) — repeating component schema
- [`Pagination`](pagination.md)
- [`FilterPanel`](filter-panel.md) — filter bar / sidebar

### Boards / timelines
- [`Kanban`](kanban.md) — model-bound, drag-and-drop
- [`Scheduler`](scheduler.md) — FullCalendar
- [`Gantt`](gantt.md) — dhtmlx-gantt
- [`Diagram`](diagram.md) — Vue Flow

### Document
- [`PDFViewer`](pdf-viewer.md)

### Layout / chrome
- [`Navigation`](navigation.md) — tabs / pills / underline
- [`BreadcrumbsComponent`](breadcrumbs.md)
- [`Icon`](icon.md) — Lucide
- [`TooltipComponent`](tooltip.md)

### Authorization
- [`PermissionGuard`](permission-guard.md) — wrap groups behind a permission

---

## Adding a new component

See [`CONTRIBUTING.md`](../CONTRIBUTING.md). Briefly:

1. Create `src/Components/MyComponent.php` extending `BaseComponent`.
2. Implement `componentType()` → string and `componentProps()` → array.
3. Build the matching `resources/js/components/darejer/components/MyComponentComponent.vue`.
4. Register it in `resources/js/pages/Screen.vue` so the renderer recognises the type string.
5. Create `docs/components/my-component.md`.
6. Add the entry to this index.
7. Update `CHANGELOG.md`.
