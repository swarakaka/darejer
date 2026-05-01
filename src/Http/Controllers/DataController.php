<?php

namespace Darejer\Http\Controllers;

use Darejer\Data\DataQuery;
use Darejer\Data\DataTransformer;
use Darejer\Data\ModelRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

class DataController extends Controller
{
    /**
     * List records for a registered model.
     *
     * Supports: search, filters, sorting, field selection, relation loading,
     * pagination, tree nesting, Combobox shape, and optional per-model caching.
     */
    public function index(Request $request, string $model): JsonResponse
    {
        $key = 'darejer-data-'.($request->user()?->id ?? $request->ip());
        if (RateLimiter::tooManyAttempts($key, 120)) {
            return response()->json(['error' => 'Too many requests.'], 429);
        }
        RateLimiter::hit($key, 60);

        $modelClass = ModelRegistry::resolve($model);
        if (! $modelClass) {
            return response()->json(['error' => "Model [{$model}] not found."], 404);
        }

        $perPage = min((int) $request->get('per_page', 15), 500);
        $isCombobox = $request->boolean('combobox');
        $isTree = $request->boolean('tree');
        $keyField = $request->get('key', 'id');
        $labelField = $request->get('label', 'name');

        $labelFields = $request->get('label_fields');
        if (is_array($labelFields)) {
            $labelFields = array_values(array_filter(
                $labelFields,
                fn ($f) => is_string($f) && preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $f),
            )) ?: null;
        } else {
            $labelFields = null;
        }

        $labelSeparator = (string) $request->get('label_separator', ' — ');

        $transformer = new DataTransformer(
            $modelClass,
            $keyField,
            $labelField,
            $isCombobox,
            $labelFields,
            $labelSeparator,
        );

        $query = $modelClass::query();
        $query = (new DataQuery($query, $request, $modelClass))->apply();

        if ($isTree) {
            $parentField = $request->get('parent_field', 'parent_id');
            $all = $query->get();
            $data = $transformer->transformCollection($all);
            $tree = $this->buildTree($data, $parentField, $keyField);

            return response()->json([
                'data' => $tree,
                'total' => count($data),
            ]);
        }

        $cacheTtl = method_exists($modelClass, 'darejerCacheTtl')
            ? (int) (new $modelClass)->darejerCacheTtl()
            : 0;

        if ($cacheTtl > 0) {
            $cacheKey = 'darejer:data:'.$model.':'.md5($request->fullUrl());

            return Cache::remember($cacheKey, $cacheTtl, function () use ($query, $perPage, $transformer) {
                return $this->paginateResponse($query, $perPage, $transformer);
            });
        }

        return $this->paginateResponse($query, $perPage, $transformer);
    }

    protected function paginateResponse(Builder $query, int $perPage, DataTransformer $transformer): JsonResponse
    {
        $paginated = $query->paginate($perPage);

        return response()->json([
            'data' => $transformer->transformCollection($paginated->items()),
            'total' => $paginated->total(),
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
            'per_page' => $paginated->perPage(),
            'from' => $paginated->firstItem(),
            'to' => $paginated->lastItem(),
        ]);
    }

    /**
     * Update a single fillable field on a record. Translatable attributes
     * are saved per-locale when the value is a `{locale: value}` object.
     */
    public function update(Request $request, string $model, int|string $id): JsonResponse
    {
        $modelClass = ModelRegistry::resolve($model);
        if (! $modelClass) {
            return response()->json(['error' => "Model [{$model}] not found."], 404);
        }

        $record = $modelClass::findOrFail($id);
        $field = $request->input('field');
        $value = $request->input('value');

        if (! $field) {
            return response()->json(['error' => 'Field is required.'], 422);
        }

        if (! in_array($field, $record->getFillable(), true)) {
            return response()->json(['error' => "Field [{$field}] is not allowed."], 422);
        }

        if (method_exists($record, 'getTranslatableAttributes')
            && in_array($field, $record->getTranslatableAttributes(), true)) {

            $translations = is_string($value)
                ? (json_decode($value, true) ?? [])
                : (array) $value;

            foreach ($translations as $locale => $translation) {
                $record->setTranslation($field, $locale, (string) ($translation ?? ''));
            }

            $record->save();
        } else {
            $record->update([$field => $value]);
        }

        $transformer = new DataTransformer($modelClass);

        return response()->json([
            'success' => true,
            'record' => $transformer->transform($record->fresh()),
        ]);
    }

    protected function buildTree(array $items, string $parentField, string $keyField, mixed $parentId = null): array
    {
        $branch = [];

        foreach ($items as $item) {
            $itemParent = $item[$parentField] ?? null;
            $matches = ($parentId === null)
                ? ($itemParent === null || $itemParent === 0 || $itemParent === '')
                : ($itemParent == $parentId);

            if ($matches) {
                $children = $this->buildTree($items, $parentField, $keyField, $item[$keyField]);
                if ($children) {
                    $item['children'] = $children;
                }
                $branch[] = $item;
            }
        }

        return $branch;
    }
}
