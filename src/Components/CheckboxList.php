<?php

declare(strict_types=1);

namespace Darejer\Components;

use Closure;
use Illuminate\Database\Eloquent\Model;

/**
 * Multi-select rendered as a grid of checkboxes. Use this instead of
 * Combobox::multiple() when the full option set should be visible at once
 * (e.g. role → permissions). Bind to an Eloquent model via ->model() so the
 * options are fetched from `darejer.data.index`, or pass static options via
 * ->options().
 *
 * For dotted permission-style labels, ->groupBySeparator('.') auto-groups
 * options by the segment before the first separator with a per-group
 * select-all toggle.
 */
class CheckboxList extends BaseComponent
{
    protected ?string $modelClass = null;

    protected string $keyField = 'id';

    protected string $labelField = 'name';

    protected ?Closure $queryScope = null;

    protected ?string $dataUrl = null;

    /** @var array<int, array{value: string, label: string}>|null */
    protected ?array $staticOptions = null;

    protected bool $disabled = false;

    protected int $columns = 1;

    protected ?string $groupBySeparator = null;

    protected bool $searchable = false;

    /**
     * @param  class-string<Model>  $modelClass
     */
    public function model(string $modelClass, string $key = 'id', string $label = 'name'): static
    {
        $this->modelClass = $modelClass;
        $this->keyField = $key;
        $this->labelField = $label;

        $modelSlug = strtolower(class_basename($modelClass));
        $this->dataUrl = route('darejer.data.index', ['model' => $modelSlug]);

        return $this;
    }

    /**
     * @param  array<int|string, string>  $options
     */
    public function options(array $options): static
    {
        $this->dataUrl = null;
        $this->staticOptions = collect($options)
            ->map(fn ($label, $value) => ['value' => (string) $value, 'label' => $label])
            ->values()
            ->all();

        return $this;
    }

    public function columns(int $columns): static
    {
        $this->columns = max(1, $columns);

        return $this;
    }

    /**
     * Auto-group options by the segment of the label preceding the first
     * occurrence of `$separator`. Each group renders with a "select all"
     * checkbox. Use `'.'` for dotted permission names like
     * `sales.invoice.create`.
     */
    public function groupBySeparator(string $separator): static
    {
        $this->groupBySeparator = $separator;

        return $this;
    }

    public function searchable(bool $searchable = true): static
    {
        $this->searchable = $searchable;

        return $this;
    }

    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;

        return $this;
    }

    protected function componentType(): string
    {
        return 'CheckboxList';
    }

    protected function componentProps(): array
    {
        return [
            'dataUrl' => $this->dataUrl,
            'staticOptions' => $this->staticOptions,
            'keyField' => $this->keyField,
            'labelField' => $this->labelField,
            'columns' => $this->columns,
            'groupBySeparator' => $this->groupBySeparator,
            'searchable' => $this->searchable ?: null,
            'disabled' => $this->disabled ?: null,
        ];
    }
}
