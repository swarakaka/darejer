<?php

namespace Darejer\Components;

use BackedEnum;
use Closure;
use Darejer\Support\EnumOptions;
use Illuminate\Database\Eloquent\Model;

/**
 * A read-only display field used on Show screens. Renders a value using the
 * element that matches its type — badges for status enums, formatted dates,
 * formatted numbers/money, Yes/No badges for booleans — never a disabled input.
 *
 * @phpstan-type DisplayType 'text'|'badge'|'date'|'datetime'|'number'|'money'|'boolean'
 */
class Display extends BaseComponent
{
    /** @var DisplayType */
    protected string $displayType = 'text';

    /** Static URL string or Closure(record, user) returning a URL (or null). */
    protected string|Closure|null $url = null;

    protected bool $external = false;

    /** @var array<string, string>|null Map of value → badge variant. */
    protected ?array $badgeMap = null;

    /** @var array<string, string>|null Map of value → translated label. */
    protected ?array $badgeLabels = null;

    protected ?string $dateFormat = null;

    protected int $decimals = 0;

    protected ?string $currencyField = null;

    protected ?string $decimalsField = null;

    protected ?string $booleanTrueLabel = null;

    protected ?string $booleanFalseLabel = null;

    protected ?string $prefix = null;

    protected ?string $suffix = null;

    protected ?string $emptyText = null;

    protected bool $translatable = false;

    /**
     * Render the value as a colored badge using the given value→variant map.
     *
     * Variants: 'success' | 'warning' | 'danger' | 'info' | 'neutral'.
     * Accepts either an array or a backed-enum class string that exposes
     * per-case colors via `color()` instance methods or a static `colors()`.
     *
     *   Display::make('status')->badge(['posted' => 'success', 'draft' => 'neutral'])
     *   Display::make('status')->badge(DocumentStatus::class)
     *
     * @param  array<string, string>|class-string<BackedEnum>  $colorMap
     */
    public function badge(array|string $colorMap): static
    {
        $this->displayType = 'badge';
        $this->badgeMap = EnumOptions::colors($colorMap);
        $this->badgeLabels = is_string($colorMap) ? EnumOptions::labels($colorMap) : null;

        return $this;
    }

    /**
     * Render as a date. Default format `Y/m/d`.
     */
    public function date(string $format = 'Y/m/d'): static
    {
        $this->displayType = 'date';
        $this->dateFormat = $format;

        return $this;
    }

    /** Render as a date+time. Default `Y/m/d H:i`. */
    public function dateTime(string $format = 'Y/m/d H:i'): static
    {
        $this->displayType = 'datetime';
        $this->dateFormat = $format;

        return $this;
    }

    /** Render as a localized number with `$decimals` fraction digits. */
    public function number(int $decimals = 0): static
    {
        $this->displayType = 'number';
        $this->decimals = $decimals;

        return $this;
    }

    /**
     * Render as money — localized number with `$decimals` fraction digits.
     * Pass `$currencyField` (a record key like `currency_code`) to suffix the
     * resolved currency code at the end.
     *
     * Pass `$decimalsField` to let the frontend pick the fraction-digit count
     * from a record path per render — e.g. `currency.minor_units` so IQD
     * (0 dp) and USD (2 dp) format correctly without controller-side math.
     * Falls back to `$decimals` when the path is missing or non-numeric.
     */
    public function money(int $decimals = 2, ?string $currencyField = null, ?string $decimalsField = null): static
    {
        $this->displayType = 'money';
        $this->decimals = $decimals;
        $this->currencyField = $currencyField;
        $this->decimalsField = $decimalsField;

        return $this;
    }

    /** Render a boolean value as a Yes/No badge. */
    public function boolean(?string $trueLabel = null, ?string $falseLabel = null): static
    {
        $this->displayType = 'boolean';
        $this->booleanTrueLabel = $trueLabel ?? __('Yes');
        $this->booleanFalseLabel = $falseLabel ?? __('No');

        return $this;
    }

    public function prefix(string $prefix): static
    {
        $this->prefix = $prefix;

        return $this;
    }

    public function suffix(string $suffix): static
    {
        $this->suffix = $suffix;

        return $this;
    }

    /** Override the placeholder rendered when the value is empty. Default `—`. */
    public function emptyText(string $emptyText): static
    {
        $this->emptyText = $emptyText;

        return $this;
    }

    /**
     * Mark the value as translatable JSON (e.g. `{"en": "Hello", "ar": "مرحبا"}`).
     * The frontend resolves the active locale, falling back to the configured
     * default language. Use for plain text and badge displays whose underlying
     * column is cast as a translatable array on the model.
     */
    public function translatable(bool $translatable = true): static
    {
        $this->translatable = $translatable;

        return $this;
    }

    /**
     * Render the value as a clickable link. The frontend wraps text-style
     * displays (text/date/datetime/number/money) in an Inertia `<Link>` for
     * internal URLs, or an `<a target="_blank">` when `$external` is true.
     * Badge and boolean displays are not wrapped — their visual semantics
     * already imply state, not navigation.
     *
     *   Display::make('journal_voucher.voucher_no')
     *       ->link(fn ($record) => route('journal_vouchers.show', $record->journal_voucher_id))
     *
     * The closure receives the parent screen's record and the authenticated
     * user (mirroring `visible()`). Return `null` to render without a link
     * — useful when a related id may be missing.
     *
     * @param  string|Closure(Model|array|null $record, mixed $user): ?string  $url
     */
    public function link(string|Closure $url, bool $external = false): static
    {
        $this->url = $url;
        $this->external = $external;

        return $this;
    }

    protected function resolveUrl(): ?string
    {
        if ($this->url === null) {
            return null;
        }

        if ($this->url instanceof Closure) {
            $resolved = ($this->url)($this->visibilityRecord, auth()->user());

            return $resolved === null ? null : (string) $resolved;
        }

        return $this->url;
    }

    protected function componentType(): string
    {
        return 'Display';
    }

    protected function componentProps(): array
    {
        return [
            'displayType' => $this->displayType,
            'badgeMap' => $this->badgeMap,
            'badgeLabels' => $this->badgeLabels,
            'dateFormat' => $this->dateFormat,
            'decimals' => $this->decimals ?: null,
            'currencyField' => $this->currencyField,
            'decimalsField' => $this->decimalsField,
            'booleanTrueLabel' => $this->booleanTrueLabel,
            'booleanFalseLabel' => $this->booleanFalseLabel,
            'prefix' => $this->prefix,
            'suffix' => $this->suffix,
            'emptyText' => $this->emptyText,
            'translatable' => $this->translatable ?: null,
            'url' => $this->resolveUrl(),
            'external' => $this->external ?: null,
        ];
    }
}
