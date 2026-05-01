# PDFViewer

> Embedded PDF viewer. Reads the source URL either inline (`->src()`) or from a field on the surrounding record (`->srcField()`).

## Import / usage

```php
use Darejer\Components\PDFViewer;

PDFViewer::make('invoice_pdf')
    ->srcField('pdf_url')
    ->height(800)
```

## Props

Inherits the shared API from `BaseComponent`.

| Method | Type | Default | Description |
|---|---|---|---|
| `src($url)` | `string` | `null` | Static PDF URL. |
| `srcField($field)` | `string` | `null` | Read the URL from this field on the record. |
| `height($px)` | `int` | `600` | Viewer height in pixels. |
| `noToolbar()` | — | toolbar `true` | Hide the browser PDF toolbar. |
| `noDownload()` | — | download `true` | Hide the download button. |
| `disabled($flag = true)` | `bool` | `false` | |

`src` takes precedence over `srcField`.

## Slots

None.

## Events

None.

## Examples

### Basic — static URL

```php
PDFViewer::make('manual')->src('/storage/manuals/getting-started.pdf')
```

### Intermediate — record-bound

```php
Screen::make('Invoice')
    ->record($invoice)
    ->components([
        PDFViewer::make('invoice_pdf')
            ->srcField('pdf_url')
            ->height(900)
            ->noDownload(),
    ])
    ->render();
```

### Real-world — view-only

```php
PDFViewer::make('contract')
    ->srcField('signed_contract_url')
    ->noToolbar()
    ->noDownload()
    ->disabled(! auth()->user()->can('contracts.view'))
```

## Accessibility

- The embedded `<iframe>` exposes the PDF's own viewer — most browsers provide built-in keyboard navigation (`PageUp`/`PageDown`, `Cmd/Ctrl+F`, etc.).
- Set a meaningful `title()` on the surrounding screen so screen-reader users know what the embedded viewer contains.

## Related

- [`FileUpload`](file-upload.md) — upload a PDF.
