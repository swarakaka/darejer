<?php

namespace Darejer\DataGrid;

use Closure;

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

    protected ?string $displayType = null;   // 'date' | 'datetime' | 'boolean' | 'plain' | …

    protected ?string $dateFormat = null;   // PHP date() format string

    protected ?string $booleanTrueLabel = null;

    protected ?string $booleanFalseLabel = null;

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
     * @param  array<string, string>  $colorMap
     */
    public function badge(array $colorMap): static
    {
        $this->badge = json_encode($colorMap);

        return $this;
    }

    /**
     * Render the column value as a date using the given PHP date() format.
     * Default is `Y-m-d` (locale-independent ISO short form).
     *
     * The value is parsed by Carbon::parse() server-side at render time, so
     * the JSON sent to the frontend already contains the formatted string —
     * no per-column JS formatter is needed.
     */
    public function date(string $format = 'Y-m-d'): static
    {
        $this->displayType = 'date';
        $this->dateFormat = $format;

        return $this;
    }

    /**
     * Render the column value as a date+time. Default `Y-m-d H:i`.
     */
    public function dateTime(string $format = 'Y-m-d H:i'): static
    {
        $this->displayType = 'datetime';
        $this->dateFormat = $format;

        return $this;
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
            'displayType' => $this->displayType,
            'dateFormat' => $this->dateFormat,
        ], fn ($v) => $v !== null && $v !== false);
    }
}
