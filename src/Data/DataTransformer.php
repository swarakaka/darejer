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

    protected bool $isCombobox;

    public function __construct(
        string $modelClass,
        string $keyField = 'id',
        string $labelField = 'name',
        bool $isCombobox = false,
    ) {
        $this->modelClass = $modelClass;
        $this->keyField = $keyField;
        $this->labelField = $labelField;
        $this->isCombobox = $isCombobox;
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
                'label' => (string) ($arr[$this->labelField] ?? ''),
            ];
        }

        return $arr;
    }

    public function transformCollection(iterable $items): array
    {
        return collect($items)
            ->map(fn (Model $item) => $this->transform($item))
            ->all();
    }
}
