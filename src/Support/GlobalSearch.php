<?php

declare(strict_types=1);

namespace Darejer\Support;

use Darejer\Concerns\Searchable;
use Darejer\Data\ModelRegistry;
use Illuminate\Database\Eloquent\Model;

/**
 * Powers the topbar global search. Walks every model registered in
 * `ModelRegistry`, keeps the ones using the `Searchable` trait, runs
 * the LIKE scope on each, and folds the matches into a flat list of
 * dropdown items.
 *
 * Per-model dedup happens via the registry slug — a model registered
 * under multiple aliases ("customer-account" and "customeraccount")
 * is searched once.
 */
class GlobalSearch
{
    /**
     * @return array{
     *     query: string,
     *     groups: list<array{
     *         slug: string,
     *         type: string,
     *         items: list<array{id: int|string, label: string, subtitle: ?string, url: ?string}>
     *     }>,
     *     total: int
     * }
     */
    public static function search(string $term, int $perModel = 5): array
    {
        $term = trim($term);
        $groups = [];
        $total = 0;

        if ($term === '') {
            return ['query' => $term, 'groups' => [], 'total' => 0];
        }

        foreach (self::searchableModels() as $slug => $modelClass) {
            /** @var Model $instance */
            $instance = new $modelClass;

            /** @var list<string> $columns */
            $columns = $instance->getSearchableAttributes();
            if ($columns === []) {
                continue;
            }

            $records = $modelClass::query()
                ->searchableTerm($term)
                ->limit($perModel)
                ->get();

            if ($records->isEmpty()) {
                continue;
            }

            $items = [];
            foreach ($records as $record) {
                $items[] = [
                    'id' => $record->getKey(),
                    'label' => $record->getSearchableLabel() ?? '#'.$record->getKey(),
                    'subtitle' => $record->getSearchableSubtitle(),
                    'url' => $record->getSearchableUrl(),
                ];
            }

            $groups[] = [
                'slug' => $slug,
                'type' => $instance->getSearchableTypeLabel(),
                'items' => $items,
            ];
            $total += count($items);
        }

        return ['query' => $term, 'groups' => $groups, 'total' => $total];
    }

    /**
     * Registered models that use the `Searchable` trait, deduped by class
     * (so multiple slug aliases for the same model only appear once,
     * keyed by their first slug).
     *
     * @return array<string, class-string<Model>>
     */
    public static function searchableModels(): array
    {
        $seen = [];
        $out = [];

        foreach (ModelRegistry::all() as $slug => $modelClass) {
            if (isset($seen[$modelClass]) || ! self::isSearchable($modelClass)) {
                continue;
            }

            $seen[$modelClass] = true;
            $out[$slug] = $modelClass;
        }

        return $out;
    }

    public static function isSearchable(string $modelClass): bool
    {
        if (! class_exists($modelClass)) {
            return false;
        }

        $traits = class_uses_recursive($modelClass);

        return isset($traits[Searchable::class]);
    }
}
