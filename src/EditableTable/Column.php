<?php

namespace Darejer\EditableTable;

use Illuminate\Database\Eloquent\Model;

/**
 * Fluent definition for a single EditableTable column.
 *
 * Mirrors the shape of {@see \Darejer\DataGrid\Column} so columns are declared
 * with a chainable API instead of opaque associative arrays.
 *
 *   Column::make('item_id')->label('Item')->combobox(Item::class)
 *       ->showPrice('selling_price')->showImage('image')
 *       ->fillFrom(['rate' => 'selling_price', 'uom_id' => 'default_uom_id'])
 */
class Column
{
    protected string $field;

    protected string $label = '';

    protected string $type = 'text';

    protected ?string $width = null;

    protected bool $disabled = false;

    protected ?string $placeholder = null;

    /** @var array<int,array{value:string,label:string}>|null */
    protected ?array $options = null;

    // ── combobox-only ────────────────────────────────────────────────────────
    protected ?string $dataUrl = null;

    protected string $keyField = 'id';

    protected string $labelField = 'name';

    protected ?string $priceField = null;

    protected ?string $imageField = null;

    /** @var string[] */
    protected array $optionFields = [];

    /** @var array<string,string> */
    protected array $fillFrom = [];

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

    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;

        return $this;
    }

    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    // ── Type setters ─────────────────────────────────────────────────────────

    public function text(): static
    {
        $this->type = 'text';

        return $this;
    }

    public function number(): static
    {
        $this->type = 'number';

        return $this;
    }

    public function date(): static
    {
        $this->type = 'date';

        return $this;
    }

    public function checkbox(): static
    {
        $this->type = 'checkbox';

        return $this;
    }

    /**
     * @param  array<string,string>  $options  ['value' => 'Label']
     */
    public function select(array $options): static
    {
        $this->type = 'select';
        $this->options = collect($options)
            ->map(fn ($label, $value) => ['value' => (string) $value, 'label' => $label])
            ->values()
            ->all();

        return $this;
    }

    /**
     * Searchable combobox bound to a registered Eloquent model. Each option
     * renders its label and — when configured — a price and image. Selecting
     * an option may auto-fill sibling row columns via {@see fillFrom()}.
     *
     * @param  class-string<Model>  $modelClass
     */
    public function combobox(string $modelClass, string $keyField = 'id', string $labelField = 'name'): static
    {
        $this->type = 'combobox';
        $this->keyField = $keyField;
        $this->labelField = $labelField;

        $modelSlug = strtolower(class_basename($modelClass));
        $this->dataUrl = route('darejer.data.index', ['model' => $modelSlug]);

        return $this;
    }

    public function dataUrl(string $url): static
    {
        $this->dataUrl = $url;

        return $this;
    }

    public function showPrice(string $field = 'price'): static
    {
        $this->priceField = $field;

        return $this;
    }

    public function showImage(string $field = 'image'): static
    {
        $this->imageField = $field;

        return $this;
    }

    /**
     * Map columns in the same row to fields on the selected combobox record.
     *
     * @param  array<string,string>  $mapping  rowColumnField => recordField
     */
    public function fillFrom(array $mapping): static
    {
        $this->fillFrom = $mapping;

        return $this;
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function toArray(): array
    {
        $base = [
            'field' => $this->field,
            'label' => $this->label,
            'type' => $this->type,
            'width' => $this->width,
            'disabled' => $this->disabled ?: null,
            'placeholder' => $this->placeholder,
            'options' => $this->options,
        ];

        if ($this->type === 'combobox') {
            $extras = array_values(array_unique(array_filter([
                $this->keyField,
                $this->labelField,
                $this->priceField,
                $this->imageField,
                ...array_values($this->fillFrom),
            ])));

            $base = array_merge($base, [
                'dataUrl' => $this->dataUrl,
                'keyField' => $this->keyField,
                'labelField' => $this->labelField,
                'priceField' => $this->priceField,
                'imageField' => $this->imageField,
                'fillFrom' => $this->fillFrom ?: null,
                'optionFields' => $extras,
            ]);
        }

        return array_filter($base, fn ($v) => $v !== null && $v !== false);
    }
}
