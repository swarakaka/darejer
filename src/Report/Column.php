<?php

namespace Darejer\Report;

/**
 * Column descriptor for report tables rendered by `Screen::reportColumns()`.
 *
 * Reports unioned across multiple sources can't lean on the DataTable's
 * Eloquent-model-bound Column. This class is a lean column schema — label,
 * alignment, display type — consumed by ReportResults.vue on the frontend.
 *
 * Use the fluent helpers (`->date()`, `->number()`, `->money()`) to declare
 * how a column should render so the frontend doesn't have to sniff values.
 */
class Column
{
    protected string $field;

    protected string $label = '';

    protected ?string $width = null;

    protected bool $hidden = false;

    protected string $align = 'left';

    protected ?string $displayType = null;   // 'date' | 'datetime' | 'number' | 'money' | 'boolean' | 'plain'

    protected ?string $dateFormat = null;

    protected int $decimals = 2;

    protected ?string $currencyField = null;

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

    public function alignLeft(): static
    {
        $this->align = 'left';

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

    /**
     * Render the value as a locale-formatted date. Frontend parses the value
     * with `new Date(...)` so any ISO-8601 / `Y-m-d` string works.
     */
    public function date(): static
    {
        $this->displayType = 'date';

        return $this;
    }

    public function dateTime(): static
    {
        $this->displayType = 'datetime';

        return $this;
    }

    /**
     * Render the value as a localized number with `$decimals` fraction digits.
     * Implies right-alignment.
     */
    public function number(int $decimals = 2): static
    {
        $this->displayType = 'number';
        $this->decimals = $decimals;
        $this->align = 'right';

        return $this;
    }

    /**
     * Render the value as money — localized number with `$decimals` fraction
     * digits, optionally suffixed with the currency code resolved from
     * `$currencyField` (a dot-notated path on the row, e.g. `currency.code`).
     * Implies right-alignment.
     */
    public function money(int $decimals = 2, ?string $currencyField = null): static
    {
        $this->displayType = 'money';
        $this->decimals = $decimals;
        $this->currencyField = $currencyField;
        $this->align = 'right';

        return $this;
    }

    public function boolean(): static
    {
        $this->displayType = 'boolean';

        return $this;
    }

    public function plain(): static
    {
        $this->displayType = 'plain';

        return $this;
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function toArray(): array
    {
        return array_filter([
            'field' => $this->field,
            'label' => $this->label,
            'width' => $this->width,
            'hidden' => $this->hidden ?: null,
            'align' => $this->align !== 'left' ? $this->align : null,
            'displayType' => $this->displayType,
            'dateFormat' => $this->dateFormat,
            'decimals' => $this->displayType === 'number' || $this->displayType === 'money' ? $this->decimals : null,
            'currencyField' => $this->currencyField,
        ], fn ($v) => $v !== null && $v !== false);
    }
}
