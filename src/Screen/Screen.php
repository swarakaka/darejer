<?php

namespace Darejer\Screen;

use Darejer\Screen\Concerns\HasActions;
use Darejer\Screen\Concerns\HasComponents;
use Darejer\Screen\Concerns\HasDialog;
use Darejer\Screen\Concerns\HasRecord;
use Inertia\Inertia;
use Inertia\Response;

class Screen
{
    use HasActions, HasComponents, HasDialog, HasRecord;

    protected string $title = '';

    protected string $vueComponent = 'Screen';

    protected array $breadcrumbs = [];

    protected array $sections = [];

    protected array $tabs = [];

    protected array $extraProps = [];

    protected bool $fullWidth = false;

    protected function __construct(string $title)
    {
        $this->title = $title;
    }

    // ── Fluent factory ───────────────────────────────────────────────────────

    public static function make(string $title): static
    {
        return new static($title);
    }

    // ── Fluent setters ───────────────────────────────────────────────────────

    public function title(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function breadcrumbs(array $breadcrumbs): static
    {
        $this->breadcrumbs = $breadcrumbs;

        return $this;
    }

    /**
     * Define FastTab sections for the screen.
     *
     * @param  Section[]  $sections
     */
    public function sections(array $sections): static
    {
        foreach ($sections as $section) {
            if (! $section instanceof Section) {
                throw new \InvalidArgumentException(
                    'Screen::sections() expects an array of '.Section::class.' instances.'
                );
            }
        }

        $this->sections = $sections;

        return $this;
    }

    /**
     * Define horizontal tabs for the screen. Tabs render as a horizontal bar
     * above the form body; each tab shows its components when active.
     *
     * @param  Tab[]  $tabs
     */
    public function tabs(array $tabs): static
    {
        foreach ($tabs as $tab) {
            if (! $tab instanceof Tab) {
                throw new \InvalidArgumentException(
                    'Screen::tabs() expects an array of '.Tab::class.' instances.'
                );
            }
        }

        $this->tabs = $tabs;

        return $this;
    }

    /**
     * Merge any extra Inertia props that don't fit the standard structure.
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

    // ── Serialization ────────────────────────────────────────────────────────

    public function toArray(): array
    {
        return array_merge([
            'title' => $this->title,
            'dialog' => $this->isDialog,
            'dialogSize' => $this->dialogSize,
            'record' => $this->serializeRecord(),
            'components' => $this->serializeComponents(),
            'actions' => $this->serializeActions(),
            'breadcrumbs' => $this->breadcrumbs,
            'sections' => $this->sections ? array_map(fn (Section $s) => $s->toArray(), $this->sections) : null,
            'tabs' => $this->tabs ? array_map(fn (Tab $t) => $t->toArray(), $this->tabs) : null,
            'fullWidth' => $this->fullWidth ?: null,
        ], $this->extraProps);
    }

    // ── Render ───────────────────────────────────────────────────────────────

    /**
     * Serialize the screen and return an Inertia response.
     * Always renders the generic Screen.vue component.
     *
     * When the request is XHR-style and explicitly wants JSON (Accept:
     * application/json or X-Requested-With: XMLHttpRequest) but doesn't
     * carry the Inertia header — i.e. it's coming from `useHttp` rather
     * than the Inertia router — flip the X-Inertia header on so Inertia
     * serves the page as JSON. The Combobox inline-create dialog relies
     * on this to fetch the create form's schema without navigating.
     */
    public function render(): Response
    {
        $request = request();

        if ($request && $request->expectsJson() && ! $request->header('X-Inertia')) {
            $request->headers->set('X-Inertia', 'true');
            // Also align the version header so Inertia's middleware doesn't
            // 409-bounce us to a hard navigation when the useHttp call
            // didn't carry an X-Inertia-Version of its own.
            $request->headers->set('X-Inertia-Version', Inertia::getVersion());
        }

        // Share breadcrumbs on the Inertia frame so AppBreadcrumbs.vue picks them up
        // (it reads from page.props.breadcrumbs, which resolves from shared props
        // when a page prop of the same name isn't set).
        Inertia::share('breadcrumbs', $this->breadcrumbs);

        return Inertia::render($this->vueComponent, $this->toArray());
    }
}
