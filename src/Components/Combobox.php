<?php

namespace Darejer\Components;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Combobox extends BaseComponent
{
    protected ?string $modelClass = null;

    protected string $keyField = 'id';

    /** @var string|array<int, string> */
    protected string|array $labelField = 'name';

    /** @var array<int, string>|null */
    protected ?array $searchFields = null;

    protected ?Closure $labelClosure = null;

    protected ?Closure $queryScope = null;

    protected ?string $dataUrl = null;

    protected ?string $addUrl = null;

    protected ?string $formUrl = null;

    protected bool $addable = false;

    protected bool $multiple = false;

    protected bool $searchable = true;

    protected bool $disabled = false;

    protected bool $clearable = true;

    protected ?string $placeholder = null;

    protected ?array $staticOptions = null;

    protected ?string $prefillUrl = null;

    /**
     * Bind to an Eloquent model — Darejer auto-generates the dataUrl.
     *
     * Pass a string for a single label field (`'name'`), or an array to
     * compose the label from multiple fields (`['code', 'name']` →
     * "ABC — Widget"). When an array is given, search defaults to
     * matching across those same fields unless `->searchFields()`
     * overrides it.
     *
     * @param  class-string<Model>  $modelClass
     * @param  string|array<int, string>|Closure  $label
     */
    public function model(
        string $modelClass,
        string $key = 'id',
        string|array|Closure $label = 'name',
        ?Closure $query = null
    ): static {
        $this->modelClass = $modelClass;
        $this->keyField = $key;

        if ($label instanceof Closure) {
            $this->labelClosure = $label;
        } else {
            $this->labelField = $label;
        }

        $this->queryScope = $query;

        $modelBase = class_basename($modelClass);
        $modelSlug = strtolower($modelBase);
        $modelKebab = Str::kebab($modelBase);
        $this->dataUrl = route('darejer.data.index', ['model' => $modelSlug]);

        // Resolve the create-screen route. Resource names follow Laravel's
        // route convention — kebab-cased plural of the model basename
        // (CustomerAccount → customer-accounts.create). Fall back to the
        // lower-cased variants for legacy routes, and finally leave addUrl
        // null when nothing matches — the "Add" affordance only renders
        // when ->addable() is explicitly set anyway.
        $candidates = array_unique([
            Str::plural($modelKebab).'.create',
            $modelKebab.'.create',
            Str::plural($modelSlug).'.create',
            $modelSlug.'s.create',
            $modelSlug.'.create',
        ]);

        $routes = app('router')->getRoutes();
        foreach ($candidates as $name) {
            if ($routes->hasNamedRoute($name)) {
                $this->addUrl = route($name, [], false).'?_dialog=1';
                break;
            }
        }

        return $this;
    }

    /**
     * Provide a static options array instead of a model.
     *
     * @param  array<string, string>  $options
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

    public function dataUrl(string $url): static
    {
        $this->dataUrl = $url;

        return $this;
    }

    public function addUrl(string $url): static
    {
        $this->addUrl = $url;

        return $this;
    }

    public function addable(bool $addable = true): static
    {
        $this->addable = $addable;

        return $this;
    }

    /**
     * Bind the "Add new…" affordance to a named Form defined on the bound
     * model's controller (via its `forms()` method). The frontend will
     * fetch the form schema from `GET /{resource}/forms/{name}` via
     * `useHttp` and render it in an inline dialog — without any Inertia
     * page navigation.
     *
     * Implies ->addable(true).
     */
    public function createForm(string $name): static
    {
        $this->addable = true;

        $resolvedRoute = $this->guessFormsRouteName();
        if ($resolvedRoute !== null) {
            $this->formUrl = route($resolvedRoute, ['form' => $name], false);
        }

        return $this;
    }

    /**
     * Try to resolve the conventional `<resource>.forms` route name from
     * the bound model — for `CustomerAccount` this yields
     * `customer-accounts.forms`. Falls back through lower-case variants and
     * returns null when no match is registered.
     */
    protected function guessFormsRouteName(): ?string
    {
        if (! $this->modelClass) {
            return null;
        }

        $modelBase = class_basename($this->modelClass);
        $candidates = array_unique([
            Str::plural(Str::kebab($modelBase)).'.forms',
            Str::kebab($modelBase).'.forms',
            Str::plural(strtolower($modelBase)).'.forms',
            strtolower($modelBase).'s.forms',
            strtolower($modelBase).'.forms',
        ]);

        $routes = app('router')->getRoutes();
        foreach ($candidates as $name) {
            if ($routes->hasNamedRoute($name)) {
                return $name;
            }
        }

        return null;
    }

    public function multiple(bool $multiple = true): static
    {
        $this->multiple = $multiple;

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

    public function clearable(bool $clearable = true): static
    {
        $this->clearable = $clearable;

        return $this;
    }

    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    /**
     * Override the fields searched by the combobox input. By default,
     * search runs against the label field(s). Use this when you want to
     * search across more (or different) columns than the ones displayed
     * — e.g. display `name`, but also search by `email` and `phone`.
     *
     * @param  array<int, string>  $fields
     */
    public function searchFields(array $fields): static
    {
        $this->searchFields = array_values(array_filter($fields, 'is_string'));

        return $this;
    }

    /**
     * On selection, fetch a JSON record from `$url?id=<chosen-id>` and merge
     * the response into the surrounding form's fields. Lets create screens
     * prefill related fields (e.g. line items from a Sales Order) in place,
     * without changing the URL or losing other unsaved input.
     *
     * The endpoint must return a flat `{field: value}` object whose keys
     * match field names on the form. Array values (e.g. `lines`) replace
     * the existing array. No-op for multi-select comboboxes.
     */
    public function prefillFrom(string $url): static
    {
        $this->prefillUrl = $url;

        return $this;
    }

    protected function componentType(): string
    {
        return 'Combobox';
    }

    protected function componentProps(): array
    {
        $labelIsArray = is_array($this->labelField);
        $labelFields = $labelIsArray ? array_values($this->labelField) : null;

        return [
            'dataUrl' => $this->dataUrl,
            'staticOptions' => $this->staticOptions,
            'addUrl' => $this->addable ? $this->addUrl : null,
            'formUrl' => $this->addable ? $this->formUrl : null,
            'addable' => $this->addable,
            'multiple' => $this->multiple ?: null,
            'searchable' => $this->searchable,
            'clearable' => $this->clearable,
            'disabled' => $this->disabled ?: null,
            'placeholder' => $this->placeholder,
            'keyField' => $this->keyField,
            'labelField' => $labelIsArray ? ($labelFields[0] ?? 'name') : $this->labelField,
            'labelFields' => $labelFields,
            'searchFields' => $this->searchFields ?? $labelFields,
            'prefillUrl' => $this->prefillUrl,
        ];
    }
}
