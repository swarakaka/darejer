<?php

namespace Darejer\Table;

use BackedEnum;
use Darejer\Support\EnumOptions;

/**
 * Fluent column definition for the read-only {@see \Darejer\Components\Table}.
 *
 * Mirrors the shape of {@see \Darejer\EditableTable\Column} but is purely
 * presentational — the frontend renders values straight out of the row, no
 * inputs, no event handlers. Field names accept dot paths so eager-loaded
 * relations can be rendered without flattening on the controller:
 *
 *   Column::make('item.name')->label('Item')
 *   Column::make('rate')->money(2)->width('8rem')
 *   Column::make('status')->badge(SomeStatus::class)
 *
 * @phpstan-type ColumnType 'text'|'number'|'money'|'date'|'datetime'|'badge'|'boolean'
 */
class Column
{
    protected string $field;

    protected string $label = '';

    /** @var ColumnType */
    protected string $type = 'text';

    protected ?string $width = null;

    protected ?int $decimals = null;

    protected ?string $dateFormat = null;

    protected ?string $currencyField = null;

    protected ?string $decimalsField = null;

    /** @var array<string, string>|null Map of value → badge variant. */
    protected ?array $badgeMap = null;

    /** @var array<string, string>|null Map of value → translated label. */
    protected ?array $badgeLabels = null;

    protected ?string $booleanTrueLabel = null;

    protected ?string $booleanFalseLabel = null;

    protected bool $alignRight = false;

    protected ?string $emptyText = null;

    protected bool $translatable = false;

    protected function __construct(string $field)
    {
        $this->field = $field;
        $this->label = ucfirst(str_replace(['_', '.'], [' ', ' '], $field));
    }

    public static function make(string $field): static
    {
        return new static($field);
    }

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function width(string $width): static
    {
        $this->width = $width;

        return $this;
    }

    public function alignRight(bool $alignRight = true): static
    {
        $this->alignRight = $alignRight;

        return $this;
    }

    public function emptyText(string $emptyText): static
    {
        $this->emptyText = $emptyText;

        return $this;
    }

    /**
     * Mark the column value as translatable JSON (e.g. `{"en": "Hello", "ar": "مرحبا"}`).
     * The frontend resolves the active locale, falling back to the configured
     * default language. Use for plain text and badge columns whose underlying
     * column is cast as a translatable array on the model.
     */
    public function translatable(bool $translatable = true): static
    {
        $this->translatable = $translatable;

        return $this;
    }

    // ── Type setters ─────────────────────────────────────────────────────────

    public function text(): static
    {
        $this->type = 'text';

        return $this;
    }

    /** Render a localized number with `$decimals` fraction digits. */
    public function number(int $decimals = 0): static
    {
        $this->type = 'number';
        $this->decimals = $decimals;
        $this->alignRight = true;

        return $this;
    }

    /**
     * Render as money — localized number with `$decimals` fraction digits and
     * thousand separators. `$currencyField` / `$decimalsField` accept dot
     * paths into the parent record so per-currency precision works without
     * controller-side math (mirrors {@see \Darejer\Components\Display::money}).
     */
    public function money(int $decimals = 2, ?string $currencyField = null, ?string $decimalsField = null): static
    {
        $this->type = 'money';
        $this->decimals = $decimals;
        $this->currencyField = $currencyField;
        $this->decimalsField = $decimalsField;
        $this->alignRight = true;

        return $this;
    }

    public function date(string $format = 'Y/m/d'): static
    {
        $this->type = 'date';
        $this->dateFormat = $format;

        return $this;
    }

    public function dateTime(string $format = 'Y/m/d H:i'): static
    {
        $this->type = 'datetime';
        $this->dateFormat = $format;

        return $this;
    }

    /**
     * @param  array<string, string>|class-string<BackedEnum>  $colorMap
     */
    public function badge(array|string $colorMap): static
    {
        $this->type = 'badge';
        $this->badgeMap = EnumOptions::colors($colorMap);
        $this->badgeLabels = is_string($colorMap) ? EnumOptions::labels($colorMap) : null;

        return $this;
    }

    public function boolean(?string $trueLabel = null, ?string $falseLabel = null): static
    {
        $this->type = 'boolean';
        $this->booleanTrueLabel = $trueLabel ?? __('Yes');
        $this->booleanFalseLabel = $falseLabel ?? __('No');

        return $this;
    }

    public function toArray(): array
    {
        return array_filter([
            'field' => $this->field,
            'label' => $this->label,
            'type' => $this->type,
            'width' => $this->width,
            'decimals' => $this->decimals,
            'dateFormat' => $this->dateFormat,
            'currencyField' => $this->currencyField,
            'decimalsField' => $this->decimalsField,
            'badgeMap' => $this->badgeMap,
            'badgeLabels' => $this->badgeLabels,
            'booleanTrueLabel' => $this->booleanTrueLabel,
            'booleanFalseLabel' => $this->booleanFalseLabel,
            'alignRight' => $this->alignRight ?: null,
            'emptyText' => $this->emptyText,
            'translatable' => $this->translatable ?: null,
        ], fn ($v) => $v !== null);
    }
}
