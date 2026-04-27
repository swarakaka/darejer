<?php

namespace Darejer\Screen\Concerns;

use Illuminate\Database\Eloquent\Model;

trait HasRecord
{
    protected Model|array|null $record = null;

    public function record(Model|array|null $record): static
    {
        $this->record = $record;

        return $this;
    }

    public function getRecord(): Model|array|null
    {
        return $this->record;
    }

    protected function serializeRecord(): array
    {
        if ($this->record === null) {
            return [];
        }

        if (is_array($this->record)) {
            return $this->record;
        }

        $data = $this->record->toArray();
        $languages = config('darejer.languages', ['en']);

        // For Spatie Translatable models, always send the full {locale: value}
        // object with every configured language present (empty string if unset).
        // This lets TranslatableInput/TranslatableTextarea render all tabs
        // consistently regardless of which locales the record has been saved in.
        if (method_exists($this->record, 'getTranslatableAttributes')) {
            foreach ($this->record->getTranslatableAttributes() as $attribute) {
                $existing = $this->record->getTranslations($attribute);
                $full = [];
                foreach ($languages as $locale) {
                    $full[$locale] = $existing[$locale] ?? '';
                }
                $data[$attribute] = $full;
            }
        }

        return $data;
    }
}
