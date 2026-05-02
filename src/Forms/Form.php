<?php

namespace Darejer\Forms;

use Darejer\Actions\BaseAction;
use Darejer\Actions\CancelAction;
use Darejer\Actions\SaveAction;
use Darejer\Screen\Contracts\Componentable;
use Darejer\Screen\Screen;
use Darejer\Screen\Section;
use Darejer\Screen\Tab;
use Illuminate\Database\Eloquent\Model;
use Inertia\Response;

/**
 * A reusable, named form definition.
 *
 * Forms live on a Darejer controller's `forms()` method and double as the
 * single source of truth for that resource's create/edit screens. Each form
 * declares its fields once; controllers then pick a form, set the record +
 * save action, and render it either as JSON (inline dialog via `useHttp`)
 * or as a full Inertia page (`renderAsScreen()`) for the standard
 * create/edit routes.
 *
 * Example:
 *
 *     public function form(): Form
 *     {
 *         return Form::make('default')
 *             ->components([...])
 *             ->tabs([...])
 *             ->sections([...]);
 *     }
 *
 *     public function create()
 *     {
 *         return $this->form()
 *             ->title(__('New Quotation'))
 *             ->record(new SalesQuotation([...]))
 *             ->save(route('sales-quotations.store'), 'POST')
 *             ->renderAsScreen();
 *     }
 */
class Form
{
    protected string $name;

    protected string $title = '';

    /** @var array<int, Componentable> */
    protected array $components = [];

    protected Model|array|null $record = null;

    protected ?string $saveUrl = null;

    protected string $saveMethod = 'POST';

    protected string $saveLabel = 'Save';

    protected ?string $cancelLabel = 'Cancel';

    protected ?string $cancelUrl = null;

    /** @var array<int, BaseAction> */
    protected array $extraActions = [];

    /** @var array<int, BaseAction>|null */
    protected ?array $actionsOverride = null;

    /** @var array<int, Tab> */
    protected array $tabs = [];

    /** @var array<int, Section> */
    protected array $sections = [];

    /** @var array<int, array{label: string, url?: string}> */
    protected array $breadcrumbs = [];

    /** @var array<string, mixed> */
    protected array $extraProps = [];

    protected bool $fullWidth = false;

    protected function __construct(string $name)
    {
        $this->name = $name;
    }

    public static function make(string $name): static
    {
        return new static($name);
    }

    public function title(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @param  array<int, Componentable>  $components
     */
    public function components(array $components): static
    {
        $this->components = $components;

        return $this;
    }

    /**
     * Bind the record the form edits. Accepts an Eloquent model or a plain
     * array of attribute defaults (used by create screens).
     */
    public function record(Model|array|null $record): static
    {
        $this->record = $record;

        return $this;
    }

    /**
     * Serialize the record to a flat array — Models have their attributes
     * extracted (matching how Screen/HasRecord does it for translatable
     * fields), arrays pass through, null becomes [].
     *
     * @return array<string, mixed>
     */
    protected function serializeRecord(): array
    {
        if ($this->record === null) {
            return [];
        }

        return $this->record instanceof Model
            ? $this->record->toArray()
            : $this->record;
    }

    /**
     * Configure the form's save action. Use POST for create, PUT/PATCH for
     * update. The label defaults to "Save" but can be overridden (e.g.
     * "Create", "Update").
     */
    public function save(string $url, string $method = 'POST', ?string $label = null): static
    {
        $this->saveUrl = $url;
        $this->saveMethod = strtoupper($method);
        if ($label !== null) {
            $this->saveLabel = $label;
        }

        return $this;
    }

    public function cancel(?string $url = null, ?string $label = null): static
    {
        $this->cancelUrl = $url;
        if ($label !== null) {
            $this->cancelLabel = $label;
        }

        return $this;
    }

    public function cancelLabel(?string $label): static
    {
        $this->cancelLabel = $label;

        return $this;
    }

    /**
     * Append actions on top of the default Save/Cancel pair (e.g. Delete).
     *
     * @param  array<int, BaseAction>  $actions
     */
    public function addActions(array $actions): static
    {
        $this->extraActions = array_merge($this->extraActions, $actions);

        return $this;
    }

    /**
     * Replace the entire action toolbar. Use this only when the default
     * Save/Cancel + addActions composition won't do.
     *
     * @param  array<int, BaseAction>  $actions
     */
    public function actions(array $actions): static
    {
        $this->actionsOverride = $actions;

        return $this;
    }

    /**
     * @param  Tab[]  $tabs
     */
    public function tabs(array $tabs): static
    {
        $this->tabs = $tabs;

        return $this;
    }

    /**
     * @param  Section[]  $sections
     */
    public function sections(array $sections): static
    {
        $this->sections = $sections;

        return $this;
    }

    /**
     * @param  array<int, array{label: string, url?: string}>  $breadcrumbs
     */
    public function breadcrumbs(array $breadcrumbs): static
    {
        $this->breadcrumbs = $breadcrumbs;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $props
     */
    public function with(array $props): static
    {
        $this->extraProps = array_merge($this->extraProps, $props);

        return $this;
    }

    public function fullWidth(bool $fullWidth = true): static
    {
        $this->fullWidth = $fullWidth;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Build the action list. Honours `actions()` override when set,
     * otherwise composes Save (when configured) + Cancel + addActions.
     *
     * @return array<int, BaseAction>
     */
    protected function buildActions(): array
    {
        if ($this->actionsOverride !== null) {
            return $this->actionsOverride;
        }

        $actions = [];

        if ($this->saveUrl !== null) {
            $actions[] = SaveAction::make($this->saveLabel)
                ->url($this->saveUrl)
                ->method($this->saveMethod);
        }

        if ($this->cancelLabel !== null) {
            $cancel = CancelAction::make($this->cancelLabel);
            if ($this->cancelUrl !== null) {
                $cancel->url($this->cancelUrl);
            }
            $actions[] = $cancel;
        }

        return array_merge($actions, $this->extraActions);
    }

    /**
     * Serialize for the JSON response that frontend consumers (CreateInDialog
     * via useHttp) parse into a form schema. Used for inline-dialog mode.
     *
     * @return array{title: string, components: array, actions: array, record: array<string, mixed>}
     */
    public function toArray(): array
    {
        $components = [];
        foreach ($this->components as $component) {
            if (method_exists($component, 'withVisibilityRecord')) {
                $component->withVisibilityRecord($this->record);
            }
            $serialized = $component->toArray();
            if ($serialized !== null) {
                $components[] = $serialized;
            }
        }

        $actions = [];
        foreach ($this->buildActions() as $action) {
            if (method_exists($action, 'withVisibilityRecord')) {
                $action->withVisibilityRecord($this->record);
            }
            $serialized = $action->toArray();
            if ($serialized !== null) {
                $actions[] = $serialized;
            }
        }

        return [
            'title' => $this->title,
            'components' => $components,
            'actions' => $actions,
            'record' => $this->serializeRecord(),
        ];
    }

    /**
     * Render the form as a full Inertia Screen page (the standard
     * create/edit experience). The Screen builder is used internally so
     * tabs, sections, breadcrumbs, fullWidth, and extra props all flow
     * through unchanged.
     */
    public function renderAsScreen(): Response
    {
        return $this->buildScreen()->render();
    }

    /**
     * Render the form as a modal dialog. Pair with `ModalToggleAction` on
     * the calling page: the action navigates here with `?_dialog=1`, the
     * Screen renders as a dialog, the form's Save action posts to the
     * configured target, and any darejer component (TextInput, Combobox,
     * DatePicker, Textarea, Select, …) is supported as an input.
     *
     * @param  string  $size  xs | sm | md | lg | xl | full
     */
    public function renderAsDialog(string $size = 'md'): Response
    {
        return $this->buildScreen()->dialog($size)->render();
    }

    /**
     * Compose the Screen used by both `renderAsScreen` and `renderAsDialog`
     * — keeps the page-vs-dialog choice to a single flag.
     */
    protected function buildScreen(): Screen
    {
        $screen = Screen::make($this->title)
            ->components($this->components)
            ->actions($this->buildActions());

        if ($this->record !== null) {
            $screen->record($this->record);
        }
        if ($this->breadcrumbs !== []) {
            $screen->breadcrumbs($this->breadcrumbs);
        }
        if ($this->tabs !== []) {
            $screen->tabs($this->tabs);
        }
        if ($this->sections !== []) {
            $screen->sections($this->sections);
        }
        if ($this->extraProps !== []) {
            $screen->with($this->extraProps);
        }
        if ($this->fullWidth) {
            $screen->fullWidth();
        }

        return $screen;
    }
}
