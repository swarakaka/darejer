<?php

namespace Darejer\Data;

use Illuminate\Database\Eloquent\Model;

/**
 * Converts Eloquent model instances into the array shape Darejer's frontend
 * expects — flattening Spatie Translatable attributes to the current locale,
 * honoring an optional model-defined `darejerTransform()` hook, and switching
 * to the `{value, label}` Combobox shape when requested.
 */
class DataTransformer
{
    protected string $modelClass;

    protected string $keyField;

    protected string $labelField;

    /** @var array<int, string>|null */
    protected ?array $labelFields;

    protected string $labelSeparator;

    protected bool $isCombobox;

    /**
     * @param  array<int, string>|null  $labelFields
     */
    public function __construct(
        string $modelClass,
        string $keyField = 'id',
        string $labelField = 'name',
        bool $isCombobox = false,
        ?array $labelFields = null,
        string $labelSeparator = ' — ',
    ) {
        $this->modelClass = $modelClass;
        $this->keyField = $keyField;
        $this->labelField = $labelField;
        $this->isCombobox = $isCombobox;
        $this->labelFields = $labelFields ? array_values($labelFields) : null;
        $this->labelSeparator = $labelSeparator;
    }

    public function transform(Model $item): array
    {
        $arr = $item->toArray();

        if (method_exists($item, 'getTranslatableAttributes')) {
            foreach ($item->getTranslatableAttributes() as $attr) {
                $arr[$attr] = $item->getTranslation($attr, app()->getLocale(), false)
                           ?: $item->getTranslation($attr, config('darejer.default_language', 'en'), false)
                           ?: '';
            }
        }

        if (method_exists($item, 'darejerTransform')) {
            $arr = $item->darejerTransform($arr);
        }

        if ($this->isCombobox) {
            return [
                'value' => (string) ($arr[$this->keyField] ?? ''),
                'label' => $this->composeLabel($arr),
            ];
        }

        return $arr;
    }

    /**
     * Build the combobox label. When `labelFields` is set, join the
     * non-empty values with the configured separator (e.g. "ABC — Widget").
     * Falls back to the single `labelField` otherwise.
     *
     * @param  array<string, mixed>  $arr
     */
    protected function composeLabel(array $arr): string
    {
        if ($this->labelFields) {
            $parts = [];
            foreach ($this->labelFields as $field) {
                $value = $arr[$field] ?? null;
                if ($value === null || $value === '') {
                    continue;
                }
                $parts[] = (string) $value;
            }

            return implode($this->labelSeparator, $parts);
        }

        return (string) ($arr[$this->labelField] ?? '');
    }

    public function transformCollection(iterable $items): array
    {
        return collect($items)
            ->map(fn (Model $item) => $this->transform($item))
            ->all();
    }
}
