<?php

namespace Darejer\Kanban;

class KanbanCard
{
    protected string $titleField = 'name';

    protected ?string $subtitleField = null;

    protected ?string $badgeField = null;

    protected array $metaFields = [];

    protected ?string $avatarField = null;

    protected ?string $dateField = null;

    public static function make(): static
    {
        return new static;
    }

    public function title(string $field): static
    {
        $this->titleField = $field;

        return $this;
    }

    public function subtitle(string $field): static
    {
        $this->subtitleField = $field;

        return $this;
    }

    public function badge(string $field): static
    {
        $this->badgeField = $field;

        return $this;
    }

    /**
     * @param  array<string, string>  $fields
     */
    public function meta(array $fields): static
    {
        $this->metaFields = collect($fields)
            ->map(fn ($label, $field) => ['field' => $field, 'label' => $label])
            ->values()
            ->all();

        return $this;
    }

    public function avatar(string $field): static
    {
        $this->avatarField = $field;

        return $this;
    }

    public function date(string $field): static
    {
        $this->dateField = $field;

        return $this;
    }

    public function toArray(): array
    {
        return array_filter([
            'titleField' => $this->titleField,
            'subtitleField' => $this->subtitleField,
            'badgeField' => $this->badgeField,
            'metaFields' => $this->metaFields ?: null,
            'avatarField' => $this->avatarField,
            'dateField' => $this->dateField,
        ], fn ($v) => $v !== null);
    }
}
