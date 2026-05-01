# Signature

> Capture a handwritten signature on a canvas (signature_pad). Stored as a data URI.

## Import / usage

```php
use Darejer\Components\Signature;

Signature::make('signature')->required()
```

## Props

Inherits the shared API from `BaseComponent`.

| Method | Type | Default | Description |
|---|---|---|---|
| `height($px)` | `int` | `160` | Canvas height. Width fills the form column. |
| `penColor($color)` | `string` | `'#000000'` | CSS color for the pen stroke. |
| `backgroundColor($color)` | `string` | `'#ffffff'` | Canvas background (also the export bg). |
| `disabled($flag = true)` | `bool` | `false` | |
| `noGuide()` | — | guide `true` | Hide the dashed signature-line guide. |

## Slots

None.

## Events

None.

## Examples

### Basic

```php
Signature::make('signature')->required()
```

### Intermediate — taller, blue ink

```php
Signature::make('approval_signature')
    ->label('Approver signature')
    ->height(220)
    ->penColor('#1a56db')
    ->required()
```

### Real-world — contract acceptance

```php
Screen::make('Sign contract')
    ->record($contract)
    ->components([
        Display::make('terms')->emptyText('—'),
        CheckboxComponent::make('accepted')
            ->checkboxLabel('I have read and agree to the terms')
            ->required(),
        Signature::make('signature')
            ->dependOn('accepted', true)
            ->required(),
    ])
    ->actions([
        Actions\SaveAction::make('Sign')
            ->url(route('contracts.sign', $contract))
            ->method('POST'),
    ])
    ->render();
```

## Accessibility

- The canvas is mouse/touch/stylus-only. For keyboard or screen-reader-only users, pair with an alternative attestation field (e.g. typed-name) in your form flow.
- A "Clear" button is keyboard-reachable.

## Related

- [`FileUpload`](file-upload.md) — upload pre-existing signature images.
