<?php

namespace Darejer\Http\Controllers;

use Darejer\Forms\Form;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Base controller every host-app controller in a Darejer project should
 * extend. Subclasses get:
 *
 *  • **Auto-routing** — declare `$resource` and Darejer auto-registers the
 *    standard REST endpoints for any of these methods the subclass defines:
 *    `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`.
 *    Custom endpoints are declared with the `#[Darejer\Routing\Route]`
 *    attribute. Nothing needs to be added to `routes/web.php`.
 *
 *  • **Model binding** — setting `$model` wires implicit route-model binding
 *    for the `show`/`edit`/`update`/`destroy` parameter.
 *
 *  • **Standard JSON envelope** — `json()`, `jsonError()`, `jsonSuccess()`
 *    helpers that return Darejer's conventional response shape so every
 *    endpoint in the app speaks the same payload format.
 */
abstract class DarejerController extends BaseController
{
    /**
     * Base URI segment for auto-registered routes (e.g. 'products').
     * When null, only `#[Route]` attributes on methods are registered.
     */
    protected ?string $resource = null;

    /**
     * Route name prefix. Defaults to the `$resource` slug
     * (e.g. 'products' → `products.index`, `products.store`).
     */
    protected ?string $routeName = null;

    /**
     * Eloquent model class for implicit route-model binding on
     * `show`/`edit`/`update`/`destroy`. When null, the parameter is a raw id.
     */
    protected ?string $model = null;

    /**
     * Middleware applied to every auto-registered route on this controller.
     */
    protected array $routeMiddleware = ['web', 'auth'];

    /**
     * Optional URL prefix stacked on top of `$resource`.
     */
    protected ?string $routePrefix = null;

    /**
     * Route parameter name. Defaults to the singular of `$resource`
     * (e.g. 'products' → `{product}`).
     */
    protected ?string $parameter = null;

    /**
     * Set to false to opt out of auto-routing for this controller.
     */
    protected bool $autoRoute = true;

    public function darejerAutoRoute(): bool
    {
        return $this->autoRoute;
    }

    public function darejerResource(): ?string
    {
        return $this->resource;
    }

    public function darejerRouteName(): ?string
    {
        return $this->routeName ?? $this->resource;
    }

    public function darejerModel(): ?string
    {
        return $this->model;
    }

    public function darejerMiddleware(): array
    {
        return $this->routeMiddleware;
    }

    public function darejerRoutePrefix(): ?string
    {
        return $this->routePrefix;
    }

    public function darejerParameter(): string
    {
        return $this->parameter ?? Str::singular($this->resource ?? 'record');
    }

    /**
     * Reusable, named form definitions for this resource.
     *
     * Override in subclasses to expose forms (e.g. a "quick-create" stripped-
     * down version of the full create screen). Each Form is auto-routed at
     * `GET /{resource}/forms/{name}` by the ControllerRouteRegistrar and can
     * be loaded from the frontend via `useHttp`. The Combobox component
     * uses this to render an inline create dialog without navigating away.
     *
     * @return array<int, Form>
     */
    public function forms(): array
    {
        return [];
    }

    // ── Response helpers ────────────────────────────────────────────────

    /**
     * Darejer's standard JSON envelope.
     *
     * Paginators are unpacked into `{ data, meta }`; collections and
     * models are wrapped in `{ data }`. Any `$meta` is merged into the
     * meta block.
     */
    protected function json(mixed $data = null, int $status = 200, array $meta = []): JsonResponse
    {
        if ($data instanceof LengthAwarePaginator) {
            return response()->json([
                'data' => $data->items(),
                'meta' => array_merge([
                    'total' => $data->total(),
                    'current_page' => $data->currentPage(),
                    'last_page' => $data->lastPage(),
                    'per_page' => $data->perPage(),
                    'from' => $data->firstItem(),
                    'to' => $data->lastItem(),
                ], $meta),
            ], $status);
        }

        $payload = ['data' => $this->normalizeData($data)];
        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    /**
     * JSON error envelope — shape matches Laravel's validation error bag so
     * Inertia `useForm`/`useHttp` surface messages consistently.
     */
    protected function jsonError(
        string $message,
        int $status = 400,
        array $errors = [],
    ): JsonResponse {
        return response()->json([
            'message' => $message,
            'errors' => $errors !== [] ? $errors : (object) [],
        ], $status);
    }

    /**
     * JSON success envelope for operations that don't return a resource
     * (bulk actions, status toggles, acknowledgements).
     */
    protected function jsonSuccess(string $message = 'OK', mixed $data = null, int $status = 200): JsonResponse
    {
        $payload = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $payload['data'] = $this->normalizeData($data);
        }

        return response()->json($payload, $status);
    }

    /**
     * JSON validation error envelope — mirrors Laravel's 422 response so
     * Inertia's error bag picks up field-level messages automatically.
     */
    protected function jsonValidation(ValidatorContract|array $errors, string $message = 'The given data was invalid.'): JsonResponse
    {
        $bag = $errors instanceof ValidatorContract
            ? $errors->errors()->toArray()
            : $errors;

        return response()->json([
            'message' => $message,
            'errors' => $bag,
        ], 422);
    }

    protected function normalizeData(mixed $data): mixed
    {
        if ($data instanceof Model) {
            return $data->toArray();
        }
        if ($data instanceof EloquentCollection || $data instanceof Collection) {
            return $data->values()->all();
        }

        return $data;
    }
}
