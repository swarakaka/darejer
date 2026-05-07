<?php

namespace Darejer\TreeGrid;

use BackedEnum;
use Darejer\Support\EnumOptions;

class TreeColumn
{
    protected string $field;

    protected string $label = '';

    protected bool $sortable = false;

    protected ?string $width = null;

    protected string $align = 'left';

    protected bool $isTree = false;

    protected ?string $badge = null;

    protected ?string $badgeLabels = null;

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

    public function sortable(): static
    {
        $this->sortable = true;

        return $this;
    }

    public function width(string $width): static
    {
        $this->width = $width;

        return $this;
    }

    public function alignRight(): static
    {
        $this->align = 'right';

        return $this;
    }

    public function alignCenter(): static
    {
        $this->align = 'center';

        return $this;
    }

    /**
     * Mark this as the tree column — shows the expand/collapse arrow.
     */
    public function tree(): static
    {
        $this->isTree = true;

        return $this;
    }

    /**
     * Map case values to a badge color. Accepts either a `[value => color]`
     * array or a backed-enum class string that exposes per-case colors via
     * `color()` instance methods or a static `colors()` helper.
     *
     * Optionally pass `$labels` to override the rendered text per value.
     *
     * @param  array<string, string>|class-string<BackedEnum>  $colorMap
     * @param  array<string, string>|null  $labels
     */
    public function badge(array|string $colorMap, ?array $labels = null): static
    {
        $this->badge = json_encode(EnumOptions::colors($colorMap));

        if ($labels !== null) {
            $this->badgeLabels = json_encode($labels);
        } elseif (is_string($colorMap)) {
            $this->badgeLabels = json_encode(EnumOptions::labels($colorMap));
        }

        return $this;
    }

    public function toArray(): array
    {
        return array_filter([
            'field' => $this->field,
            'label' => $this->label,
            'sortable' => $this->sortable ?: null,
            'width' => $this->width,
            'align' => $this->align !== 'left' ? $this->align : null,
            'isTree' => $this->isTree ?: null,
            'badge' => $this->badge,
            'badgeLabels' => $this->badgeLabels,
        ], fn ($v) => $v !== null && $v !== false);
    }
}
