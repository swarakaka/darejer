# FileUpload

> Drag-and-drop file uploader. Single or multiple files, with size/type/count limits, optional image preview, and configurable storage disk + path.

## Import / usage

```php
use Darejer\Components\FileUpload;

FileUpload::make('avatar')->image()->maxSize(2048)
```

## Props

Inherits the shared API from `BaseComponent`.

| Method | Type | Default | Description |
|---|---|---|---|
| `multiple($flag = true)` | `bool` | `false` | Allow multiple files. |
| `accept($mimeTypes)` | `string[]` | `[]` | MIME types accepted (e.g. `['application/pdf']`). Empty = any. |
| `image()` | — | — | Shortcut: sets `accept = ['image/*']` and enables preview. |
| `maxSize($kb)` | `int` | `null` | Max size per file, kilobytes. |
| `maxFiles($n)` | `int` | `null` | Max total files. Implies `multiple()`. |
| `disk($name)` | `string` | `darejer.uploads.disk` (`'public'`) | Laravel storage disk. |
| `path($path)` | `string` | `darejer.uploads.path` (`'uploads'`) | Destination folder under the disk. |
| `noPreview()` | — | preview `true` | Hide image previews. |
| `disabled($flag = true)` | `bool` | `false` | |

## Slots

None.

## Events

None on the backend — uploaded file paths arrive in the form payload of the surrounding action.

## Examples

### Basic — single file

```php
FileUpload::make('contract')
    ->accept(['application/pdf'])
    ->maxSize(5_000)
```

### Intermediate — image with preview

```php
FileUpload::make('logo')
    ->image()
    ->maxSize(2_000)
    ->disk('s3')
    ->path('tenants/'.$tenant->id.'/logos')
```

### Real-world — multi-file gallery

```php
FileUpload::make('gallery')
    ->multiple()
    ->maxFiles(10)
    ->maxSize(5_000)
    ->accept(['image/jpeg', 'image/png', 'image/webp'])
```

## Accessibility

- Drop zone is keyboard-activatable (`Enter` / `Space`) — opens the file picker.
- Each uploaded file in the preview list has a remove button labelled by filename.
- Upload progress is announced via aria-live.

## Related

- [`uploads` config](../getting-started/configuration.md#uploadsdisk--uploadspath) — global defaults.
- [`Signature`](signature.md) — for handwritten signatures.
- [`PDFViewer`](pdf-viewer.md) — display uploaded PDFs.
