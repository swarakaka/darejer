<?php

namespace Darejer\DataGrid;

use BackedEnum;
use Closure;
use Darejer\Support\EnumOptions;

class Column
{
    protected string $field;

    protected string $label = '';

    protected bool $sortable = false;

    protected bool $searchable = false;

    protected ?string $width = null;

    protected bool $hidden = false;

    protected string $align = 'left';

    protected ?Closure $format = null;

    protected ?Closure $displayUsing = null;

    protected ?string $badge = null;

    protected ?string $badgeLabels = null;

    /** @var class-string<BackedEnum>|null */
    protected ?string $badgeEnum = null;

    protected ?string $textColorBy = null;

    protected ?string $textColorMap = null;

    /** @var array<string, string>|null */
    protected ?array $signColorMap = null;

    /** Prefix for the synthetic per-row field that carries a value's sign. */
    private const SIGN_FIELD_PREFIX = '__sign_';

    protected ?string $displayType = null;   // 'date' | 'datetime' | 'boolean' | 'money' | 'plain' | …

    protected ?string $dateFormat = null;   // PHP date() format string

    protected int $decimals = 2;

    protected bool $absolute = false;

    protected ?string $currencyField = null;

    protected ?string $decimalsField = null;

    protected ?string $booleanTrueLabel = null;

    protected ?string $booleanFalseLabel = null;

    protected ?string $footer = null;

    protected ?Closure $footerCallback = null;

    protected function __construct(string $field)
    {
        $this->field = $field;
        $this->label = ucfirst(str_replace('_', ' ', $field));
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

    public function sortable(bool $sortable = true): static
    {
        $this->sortable = $sortable;

        return $this;
    }

    public function searchable(bool $searchable = true): static
    {
        $this->searchable = $searchable;

        return $this;
    }

    public function width(string $width): static
    {
        $this->width = $width;

        return $this;
    }

    public function hidden(bool $hidden = true): static
    {
        $this->hidden = $hidden;

        return $this;
    }

    public function alignCenter(): static
    {
        $this->align = 'center';

        return $this;
    }

    public function alignRight(): static
    {
        $this->align = 'right';

        return $this;
    }

    public function format(Closure $format): static
    {
        $this->format = $format;

        return $this;
    }

    public function displayUsing(callable $callback): static
    {
        $this->displayUsing = $callback instanceof Closure
            ? $callback
            : Closure::fromCallable($callback);

        return $this;
    }

    /**
     * Map case values to a badge color. Accepts either a `[value => color]`
     * array or a backed-enum class string that exposes per-case colors via
     * `color()` instance methods or a static `colors()` helper.
     *
     * Optionally pass `$labels` to override the rendered text per value.
     * For boolean-keyed arrays (`'1'`/`'0'`) a translated Yes/No is used
     * automatically when labels aren't given.
     *
     * @param  array<string, string>|class-string<BackedEnum>  $colorMap
     * @param  array<string, string>|null  $labels
     */
    public function badge(array|string $colorMap, ?array $labels = null): static
    {
        $this->badge = json_encode(EnumOptions::colors($colorMap));

        if (is_string($colorMap) && is_subclass_of($colorMap, BackedEnum::class)) {
            $this->badgeEnum = $colorMap;
        }

        if ($labels !== null) {
            $this->badgeLabels = json_encode($labels);
        } elseif (is_string($colorMap)) {
            $this->badgeLabels = json_encode(EnumOptions::labels($colorMap));
        } elseif ($this->isBooleanColorMap($colorMap)) {
            $this->badgeLabels = json_encode(['1' => __('Yes'), '0' => __('No')]);
        }

        return $this;
    }

    /** @return class-string<BackedEnum>|null */
    public function getBadgeEnum(): ?string
    {
        return $this->badgeEnum;
    }

    /**
     * Color the plain-text cell value based on a sibling field on the row.
     *
     * `$field` is the dot-notated path on the row whose value selects the
     * variant from `$map`. Accepts either a `[value => color]` array or a
     * backed-enum class string that exposes per-case colors via `color()`
     * methods or a static `colors()` helper — same resolution as `badge()`.
     *
     * Variants follow the `success`/`warning`/`danger`/`info`/`neutral`
     * palette. No-op for badge columns (those have their own coloring).
     *
     * @param  array<string, string>|class-string<BackedEnum>  $colorMap
     */
    public function textColorBy(string $field, array|string $colorMap): static
    {
        $this->textColorBy = $field;
        $this->textColorMap = json_encode(EnumOptions::colors($colorMap));

        return $this;
    }

    /**
     * @param  array<string, string>  $map
     */
    protected function isBooleanColorMap(array $map): bool
    {
        $keys = array_map('strval', array_keys($map));
        sort($keys);

        return $keys === ['0', '1'];
    }

    /**
     * Render the column value as a date using the given PHP date() format.
     * Default is `Y/m/d`.
     *
     * The value is parsed by Carbon::parse() server-side at render time, so
     * the JSON sent to the frontend already contains the formatted string —
     * no per-column JS formatter is needed.
     */
    public function date(string $format = 'Y/m/d'): static
    {
        $this->displayType = 'date';
        $this->dateFormat = $format;

        return $this;
    }

    /**
     * Render the column value as a date+time. Default `Y/m/d H:i`.
     */
    public function dateTime(string $format = 'Y/m/d H:i'): static
    {
        $this->displayType = 'datetime';
        $this->dateFormat = $format;

        return $this;
    }

    /**
     * Render the column value as a localized number with `$decimals` fraction
     * digits and a thousands separator. Use for non-currency numerics like
     * counts, ratios, or quantities. Formatted server-side at render time.
     */
    public function number(int $decimals = 0): static
    {
        $this->displayType = 'number';
        $this->decimals = $decimals;

        return $this;
    }

    /**
     * Render the column value as money — a localized number with `$decimals`
     * fraction digits, optionally suffixed with a currency code resolved from
     * `$currencyField` (a dot-notated path on the row, e.g. `currency.code`).
     *
     * Pass `$decimalsField` (a dot-notated path like `currency.minor_units`)
     * to resolve the fraction-digit count per row from the record itself —
     * lets a list mix IQD (0dp) and USD (2dp) without separate columns.
     * Falls back to `$decimals` when the path is missing or non-numeric.
     *
     * Formatted server-side at render time so the JSON sent to the frontend
     * already contains the display string — pairs naturally with `alignRight()`.
     */
    public function money(int $decimals = 2, ?string $currencyField = null, ?string $decimalsField = null): static
    {
        $this->displayType = 'money';
        $this->decimals = $decimals;
        $this->currencyField = $currencyField;
        $this->decimalsField = $decimalsField;

        return $this;
    }

    /**
     * Render the column's `number`/`money` value as an unsigned magnitude —
     * the leading minus is dropped so a negative amount or quantity shows as
     * its absolute value. Use when direction is conveyed another way (row
     * color, a sibling state badge) instead of a `-` sign, per accounting
     * presentation. No-op for non-numeric display types.
     */
    public function absolute(bool $absolute = true): static
    {
        $this->absolute = $absolute;

        return $this;
    }

    /**
     * Color this column's own cell by the SIGN of its numeric value, resolved
     * server-side from the raw amount before any `absolute()`/`money()`
     * formatting. Map keys are `negative`, `zero`, `positive`; values are
     * color variants (strings or backed-enum cases like `Color::Danger`).
     *
     * Pairs naturally with `absolute()`: show the magnitude, let the color
     * carry the sign — the international "red = negative" convention. Internally
     * reuses the `textColorBy` mechanism against a synthetic sign field, so it
     * overrides any `textColorBy()` set on the same column.
     *
     * @param  array<string, string|BackedEnum>  $map
     */
    public function colorBySign(array $map): static
    {
        $this->signColorMap = EnumOptions::colors($map);
        $this->textColorBy = $this->getSignField();
        $this->textColorMap = json_encode($this->signColorMap);

        return $this;
    }

    /**
     * Name of the synthetic per-row field carrying this column's value sign,
     * or null when sign coloring isn't enabled.
     */
    public function getSignField(): ?string
    {
        return $this->signColorMap === null
            ? null
            : self::SIGN_FIELD_PREFIX.str_replace('.', '_', $this->field);
    }

    /** @return array<string, string>|null */
    public function getSignColorMap(): ?array
    {
        return $this->signColorMap;
    }

    /**
     * Render the column value as a boolean. The raw value is coerced to bool
     * server-side and replaced with the corresponding label so the frontend
     * receives a ready-to-display string.
     */
    public function boolean(?string $trueLabel = null, ?string $falseLabel = null): static
    {
        $this->displayType = 'boolean';
        $this->booleanTrueLabel = $trueLabel ?? __('Yes');
        $this->booleanFalseLabel = $falseLabel ?? __('No');

        return $this;
    }

    /** Override the auto-resolved display type. e.g. 'plain' to opt out. */
    public function display(string $type, ?string $format = null): static
    {
        $this->displayType = $type;
        if ($format !== null) {
            $this->dateFormat = $format;
        }

        return $this;
    }

    /**
     * Render a footer cell that aggregates this column across the entire
     * filtered dataset (not just the visible page).
     *
     * Pass a bare aggregator (`'sum'`, `'avg'`, `'min'`, `'max'`, `'count'`)
     * to run a SQL aggregate against this column's field — works for real
     * DB columns only.
     *
     * For accessor columns or anything needing a join/subquery, pass a
     * Closure that receives a clone of the filtered Eloquent Builder (with
     * its ORDER BY cleared) and returns the value. Return a numeric scalar
     * or stringified BigDecimal; the result is formatted with the column's
     * `number`/`money` precision.
     *
     *   ->footer(fn (Builder $q) => DB::table('lines')
     *       ->whereIn('payment_id', $q->select('id'))
     *       ->sum('amount'))
     */
    public function footer(string|Closure $aggregator): static
    {
        if ($aggregator instanceof Closure) {
            $this->footer = 'callback';
            $this->footerCallback = $aggregator;
        } else {
            $valid = ['sum', 'avg', 'min', 'max', 'count'];
            $normalized = strtolower(trim($aggregator));
            if (! in_array($normalized, $valid, true)) {
                throw new \InvalidArgumentException(
                    'DataGrid Column footer must be one of: '.implode(', ', $valid).". Got: {$aggregator}"
                );
            }
            $this->footer = $normalized;
            $this->footerCallback = null;
        }

        return $this;
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getDisplayType(): ?string
    {
        return $this->displayType;
    }

    public function getDateFormat(): ?string
    {
        return $this->dateFormat;
    }

    public function getDecimals(): int
    {
        return $this->decimals;
    }

    public function isAbsolute(): bool
    {
        return $this->absolute;
    }

    public function getCurrencyField(): ?string
    {
        return $this->currencyField;
    }

    public function getDecimalsField(): ?string
    {
        return $this->decimalsField;
    }

    public function getBooleanTrueLabel(): ?string
    {
        return $this->booleanTrueLabel;
    }

    public function getBooleanFalseLabel(): ?string
    {
        return $this->booleanFalseLabel;
    }

    public function getDisplayUsing(): ?Closure
    {
        return $this->displayUsing;
    }

    public function getFormat(): ?Closure
    {
        return $this->format;
    }

    public function getFooter(): ?string
    {
        return $this->footer;
    }

    public function getFooterCallback(): ?Closure
    {
        return $this->footerCallback;
    }

    public function toArray(): array
    {
        return array_filter([
            'field' => $this->field,
            'label' => $this->label,
            'sortable' => $this->sortable ?: null,
            'searchable' => $this->searchable ?: null,
            'width' => $this->width,
            'hidden' => $this->hidden ?: null,
            'align' => $this->align !== 'left' ? $this->align : null,
            'badge' => $this->badge,
            'badgeLabels' => $this->badgeLabels,
            'textColorBy' => $this->textColorBy,
            'textColorMap' => $this->textColorMap,
            'displayType' => $this->displayType,
            'dateFormat' => $this->dateFormat,
            'footer' => $this->footer,
        ], fn ($v) => $v !== null && $v !== false);
    }
}
