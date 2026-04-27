<?php

namespace Darejer\Components;

use Darejer\DataGrid\Column;
use Darejer\DataGrid\RowAction;

class DataGrid extends BaseComponent
{
    /** @var Column[] */
    protected array $gridColumns = [];

    /** @var RowAction[] */
    protected array $rowActions = [];

    protected ?string $dataUrl = null;

    protected int $perPage = 15;

    protected bool $selectable = false;

    protected ?string $emptyMessage = null;

    protected ?string $defaultSort = null;

    protected string $defaultOrder = 'asc';

    protected bool $searchable = true;

    /**
     * @param  Column[]  $columns
     */
    public function columns(array $columns): static
    {
        $this->gridColumns = $columns;

        return $this;
    }

    /**
     * @param  RowAction[]  $actions
     */
    public function rowActions(array $actions): static
    {
        $this->rowActions = $actions;

        return $this;
    }

    public function dataUrl(string $url): static
    {
        $this->dataUrl = $url;

        return $this;
    }

    public function model(string $modelClass): static
    {
        $slug = strtolower(class_basename($modelClass));
        $this->dataUrl = route('darejer.data.index', ['model' => $slug]);

        return $this;
    }

    public function perPage(int $perPage): static
    {
        $this->perPage = $perPage;

        return $this;
    }

    public function selectable(bool $selectable = true): static
    {
        $this->selectable = $selectable;

        return $this;
    }

    public function emptyMessage(string $message): static
    {
        $this->emptyMessage = $message;

        return $this;
    }

    public function defaultSort(string $field, string $order = 'asc'): static
    {
        $this->defaultSort = $field;
        $this->defaultOrder = $order;

        return $this;
    }

    public function searchable(bool $searchable = true): static
    {
        $this->searchable = $searchable;

        return $this;
    }

    protected function componentType(): string
    {
        return 'DataGrid';
    }

    protected function componentProps(): array
    {
        return [
            'dataUrl' => $this->dataUrl,
            'perPage' => $this->perPage,
            'selectable' => $this->selectable ?: null,
            'emptyMessage' => $this->emptyMessage,
            'defaultSort' => $this->defaultSort,
            'defaultOrder' => $this->defaultOrder,
            'searchable' => $this->searchable,
            'gridColumns' => array_map(fn (Column $c) => $c->toArray(), $this->gridColumns),
            'rowActions' => array_map(fn (RowAction $a) => $a->toArray(), $this->rowActions),
        ];
    }
}
