<?php

namespace Darejer\DataGrid;

class Filter
{
    protected string $field;

    protected string $label = '';

    protected string $type = 'text';

    protected array $options = [];

    protected ?string $placeholder = null;

    protected mixed $default = null;

    protected function __construct(string $field)
    {
        $this->field = $field;
        $this->label = ucfirst(str_replace('_', ' ', $field));
    }

    public static function make(string $field): static
    {
        return new static($field);
    }

    public static function text(string $field): static
    {
        return (new static($field))->type('text');
    }

    public static function select(string $field): static
    {
        return (new static($field))->type('select');
    }

    public static function date(string $field): static
    {
        return (new static($field))->type('date');
    }

    public static function dateRange(string $field): static
    {
        return (new static($field))->type('daterange');
    }

    public static function boolean(string $field): static
    {
        return (new static($field))->type('boolean');
    }

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function type(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @param  array<string, string>  $options
     */
    public function options(array $options): static
    {
        $this->options = $options;

        return $this;
    }

    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function default(mixed $default): static
    {
        $this->default = $default;

        return $this;
    }

    public function toArray(): array
    {
        $normalized = collect($this->options)
            ->map(fn ($label, $value) => ['value' => (string) $value, 'label' => $label])
            ->values()
            ->all();

        return array_filter([
            'field' => $this->field,
            'label' => $this->label,
            'type' => $this->type,
            'options' => $normalized ?: null,
            'placeholder' => $this->placeholder,
            'default' => $this->default,
        ], fn ($v) => $v !== null);
    }
}
