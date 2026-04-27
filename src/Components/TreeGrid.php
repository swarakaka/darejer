<?php

namespace Darejer\Components;

use Darejer\DataGrid\RowAction;
use Darejer\TreeGrid\TreeColumn;

class TreeGrid extends BaseComponent
{
    /** @var TreeColumn[] */
    protected array $treeColumns = [];

    /** @var RowAction[] */
    protected array $rowActions = [];

    protected ?string $dataUrl = null;

    protected string $parentField = 'parent_id';

    protected string $keyField = 'id';

    protected string $childrenKey = 'children';

    protected bool $expandAll = false;

    protected ?string $emptyMessage = null;

    /**
     * @param  TreeColumn[]  $columns
     */
    public function columns(array $columns): static
    {
        $this->treeColumns = $columns;

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
        $this->dataUrl = route('darejer.data.index', ['model' => $slug, 'tree' => '1']);

        return $this;
    }

    public function parentField(string $field): static
    {
        $this->parentField = $field;

        return $this;
    }

    public function keyField(string $field): static
    {
        $this->keyField = $field;

        return $this;
    }

    public function expandAll(bool $expand = true): static
    {
        $this->expandAll = $expand;

        return $this;
    }

    public function emptyMessage(string $message): static
    {
        $this->emptyMessage = $message;

        return $this;
    }

    protected function componentType(): string
    {
        return 'TreeGrid';
    }

    protected function componentProps(): array
    {
        return [
            'treeColumns' => array_map(fn (TreeColumn $c) => $c->toArray(), $this->treeColumns),
            'rowActions' => array_map(fn (RowAction $a) => $a->toArray(), $this->rowActions),
            'dataUrl' => $this->dataUrl,
            'parentField' => $this->parentField,
            'keyField' => $this->keyField,
            'childrenKey' => $this->childrenKey,
            'expandAll' => $this->expandAll ?: null,
            'emptyMessage' => $this->emptyMessage,
        ];
    }
}
