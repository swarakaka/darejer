<?php

namespace Darejer\Components;

use Darejer\EditableTable\Column;

class EditableTable extends BaseComponent
{
    protected array $tableColumns = [];

    protected bool $addable = true;

    protected bool $deletable = true;

    protected bool $sortable = false;

    protected bool $disabled = false;

    protected ?int $maxRows = null;

    protected array $defaultRow = [];

    protected bool $fullWidth = true;

    /**
     * Define columns.
     *
     * Preferred form is a list of {@see Column} instances:
     *
     *   ->columns([
     *       Column::make('item_id')->combobox(Item::class)->subLabel('selling_price'),
     *       Column::make('qty')->number()->width('6rem'),
     *   ])
     *
     * Legacy associative-array form is still accepted:
     *   ['field' => 'qty', 'label' => 'Qty', 'type' => 'number', 'width' => '6rem']
     *
     * @param  array<int, Column|array<string, mixed>>  $columns
     */
    public function columns(array $columns): static
    {
        $this->tableColumns = array_map(function (Column|array $col) {
            if ($col instanceof Column) {
                return $col->toArray();
            }

            if (isset($col['options']) && is_array($col['options'])) {
                $col['options'] = collect($col['options'])
                    ->map(fn ($label, $value) => ['value' => (string) $value, 'label' => $label])
                    ->values()
                    ->all();
            }

            return $col;
        }, $columns);

        return $this;
    }

    public function addable(bool $addable = true): static
    {
        $this->addable = $addable;

        return $this;
    }

    public function deletable(bool $deletable = true): static
    {
        $this->deletable = $deletable;

        return $this;
    }

    public function sortable(bool $sortable = true): static
    {
        $this->sortable = $sortable;

        return $this;
    }

    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;

        return $this;
    }

    public function maxRows(int $max): static
    {
        $this->maxRows = $max;

        return $this;
    }

    public function defaultRow(array $defaults): static
    {
        $this->defaultRow = $defaults;

        return $this;
    }

    protected function componentType(): string
    {
        return 'EditableTable';
    }

    protected function componentProps(): array
    {
        return [
            'tableColumns' => $this->tableColumns,
            'addable' => $this->addable,
            'deletable' => $this->deletable,
            'sortable' => $this->sortable ?: null,
            'disabled' => $this->disabled ?: null,
            'maxRows' => $this->maxRows,
            'defaultRow' => $this->defaultRow ?: null,
        ];
    }
}
