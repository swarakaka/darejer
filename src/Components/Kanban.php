<?php

namespace Darejer\Components;

use Darejer\Kanban\KanbanCard;
use Darejer\Kanban\KanbanColumn;

class Kanban extends BaseComponent
{
    /** @var KanbanColumn[] */
    protected array $kanbanColumns = [];

    protected ?KanbanCard $cardTemplate = null;

    protected ?string $dataUrl = null;

    protected string $statusField = 'status';

    protected ?string $updateUrl = null;

    protected ?string $editUrl = null;

    protected bool $editDialog = false;

    protected bool $disabled = false;

    /**
     * @param  KanbanColumn[]  $columns
     */
    public function columns(array $columns): static
    {
        $this->kanbanColumns = $columns;

        return $this;
    }

    public function card(KanbanCard $card): static
    {
        $this->cardTemplate = $card;

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

    public function statusField(string $field): static
    {
        $this->statusField = $field;

        return $this;
    }

    public function updateUrl(string $url): static
    {
        $this->updateUrl = $url;

        return $this;
    }

    public function editUrl(string $url, bool $dialog = false): static
    {
        $this->editUrl = $url;
        $this->editDialog = $dialog;

        return $this;
    }

    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;

        return $this;
    }

    protected function componentType(): string
    {
        return 'Kanban';
    }

    protected function componentProps(): array
    {
        return [
            'kanbanColumns' => array_map(fn (KanbanColumn $c) => $c->toArray(), $this->kanbanColumns),
            'cardTemplate' => $this->cardTemplate?->toArray(),
            'dataUrl' => $this->dataUrl,
            'statusField' => $this->statusField,
            'updateUrl' => $this->updateUrl,
            'editUrl' => $this->editUrl,
            'editDialog' => $this->editDialog ?: null,
            'disabled' => $this->disabled ?: null,
        ];
    }
}
