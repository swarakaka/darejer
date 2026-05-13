<?php

namespace Darejer\Components;

use Illuminate\Database\Eloquent\Model;

/**
 * A numeric input specialised for monetary values: locale-aware grouping and
 * decimal separators, fixed fraction digits, and an optional built-in currency
 * picker bound to an Eloquent model.
 *
 * When `currencyPicker()` is set, the component renders a small dropdown next
 * to the amount input listing rows from the chosen model. Selecting a row
 * writes its id back to a sibling form field (default `currency_id`) and
 * automatically switches the amount's fraction digits to the selected
 * currency's decimals field (default `minor_units`).
 *
 * The frontend displays a formatted string while binding a raw numeric value,
 * so server-side validation rules like `numeric|min:0` work without parsing.
 */
class Money extends BaseComponent
{
    protected string $placeholder = '';

    protected int $decimals = 2;

    protected ?string $currency = null;

    protected ?string $currencyField = null;

    protected ?string $prefix = null;

    protected ?string $suffix = null;

    protected ?string $suffixActionUrl = null;

    protected ?string $suffixActionIcon = null;

    protected ?string $suffixActionTooltip = null;

    protected float|int|null $min = null;

    protected float|int|null $max = null;

    protected float|int|null $step = null;

    protected bool $allowNegative = true;

    protected ?string $thousandsSeparator = null;

    protected ?string $decimalSeparator = null;

    protected bool $readonly = false;

    protected bool $disabled = false;

    protected bool $autofocus = false;

    // ── Currency picker (model-bound) ────────────────────────────────────────

    protected ?string $currencyDataUrl = null;

    protected string $currencyKeyField = 'id';

    protected string $currencyLabelField = 'code';

    protected string $currencyDecimalsField = 'minor_units';

    protected ?string $currencySymbolField = null;

    protected string $currencyValueField = 'currency_id';

    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    /** Number of fraction digits to display. Defaults to 2. */
    public function decimals(int $decimals): static
    {
        $this->decimals = $decimals;

        return $this;
    }

    /**
     * Static ISO currency code rendered as a suffix (e.g. `USD`, `EUR`).
     * Use `currencyField()` to read it from the record, or `currencyPicker()`
     * to let the user pick from a model.
     */
    public function currency(string $code): static
    {
        $this->currency = $code;

        return $this;
    }

    /**
     * Record path resolving to a currency code (e.g. `currency.code`). The
     * frontend reads this from the form/record source and renders it as a
     * suffix. Takes precedence over `currency()` and `suffix()`.
     */
    public function currencyField(string $field): static
    {
        $this->currencyField = $field;

        return $this;
    }

    /**
     * Render an inline currency picker bound to an Eloquent model. On select,
     * the chosen row's id is written to `$valueField` on the surrounding form
     * and the amount input switches to that currency's decimals.
     *
     * Defaults assume a typical schema: id key, `code` label, `minor_units`
     * decimals, sibling form field `currency_id`.
     *
     * @param  class-string<Model>  $modelClass
     */
    public function currencyPicker(
        string $modelClass,
        string $valueField = 'currency_id',
        string $keyField = 'id',
        string $labelField = 'code',
        string $decimalsField = 'minor_units',
        ?string $symbolField = null,
    ): static {
        $modelSlug = strtolower(class_basename($modelClass));
        $this->currencyDataUrl = route('darejer.data.index', ['model' => $modelSlug]);
        $this->currencyValueField = $valueField;
        $this->currencyKeyField = $keyField;
        $this->currencyLabelField = $labelField;
        $this->currencyDecimalsField = $decimalsField;
        $this->currencySymbolField = $symbolField;

        return $this;
    }

    /** Override the auto-resolved currency picker dataUrl. */
    public function currencyDataUrl(string $url): static
    {
        $this->currencyDataUrl = $url;

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

    /**
     * Render the suffix as a clickable button that opens `$url` in an inline
     * dialog (re-using the same CreateInDialog mechanism as Combobox::addable).
     * The icon (Lucide name) defaults to `Plus`; the tooltip is shown on hover.
     */
    public function suffixAction(string $url, ?string $icon = null, ?string $tooltip = null): static
    {
        $this->suffixActionUrl = $url;
        $this->suffixActionIcon = $icon;
        $this->suffixActionTooltip = $tooltip;

        return $this;
    }

    public function min(float|int $min): static
    {
        $this->min = $min;

        return $this;
    }

    public function max(float|int $max): static
    {
        $this->max = $max;

        return $this;
    }

    public function step(float|int $step): static
    {
        $this->step = $step;

        return $this;
    }

    /** Whether negative values are permitted. Defaults to true. */
    public function allowNegative(bool $allowNegative = true): static
    {
        $this->allowNegative = $allowNegative;

        return $this;
    }

    /** Override the locale's grouping separator (e.g. `,` or `.`). */
    public function thousandsSeparator(string $separator): static
    {
        $this->thousandsSeparator = $separator;

        return $this;
    }

    /** Override the locale's decimal separator (e.g. `.` or `,`). */
    public function decimalSeparator(string $separator): static
    {
        $this->decimalSeparator = $separator;

        return $this;
    }

    public function readonly(bool $readonly = true): static
    {
        $this->readonly = $readonly;

        return $this;
    }

    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;

        return $this;
    }

    public function autofocus(): static
    {
        $this->autofocus = true;

        return $this;
    }

    protected function componentType(): string
    {
        return 'Money';
    }

    protected function componentProps(): array
    {
        return [
            'placeholder' => $this->placeholder ?: null,
            'decimals' => $this->decimals,
            'currency' => $this->currency,
            'currencyField' => $this->currencyField,
            'prefix' => $this->prefix,
            'suffix' => $this->suffix,
            'suffixActionUrl' => $this->suffixActionUrl,
            'suffixActionIcon' => $this->suffixActionIcon,
            'suffixActionTooltip' => $this->suffixActionTooltip,
            'min' => $this->min,
            'max' => $this->max,
            'step' => $this->step,
            'allowNegative' => $this->allowNegative,
            'thousandsSeparator' => $this->thousandsSeparator,
            'decimalSeparator' => $this->decimalSeparator,
            'readonly' => $this->readonly ?: null,
            'disabled' => $this->disabled ?: null,
            'autofocus' => $this->autofocus ?: null,

            'currencyDataUrl' => $this->currencyDataUrl,
            'currencyValueField' => $this->currencyDataUrl ? $this->currencyValueField : null,
            'currencyKeyField' => $this->currencyDataUrl ? $this->currencyKeyField : null,
            'currencyLabelField' => $this->currencyDataUrl ? $this->currencyLabelField : null,
            'currencyDecimalsField' => $this->currencyDataUrl ? $this->currencyDecimalsField : null,
            'currencySymbolField' => $this->currencyDataUrl ? $this->currencySymbolField : null,
        ];
    }
}
