<?php

namespace Darejer\Actions;

use Darejer\Forms\Form;
use Darejer\Screen\Contracts\Componentable;

/**
 * Action that opens a dialog/modal when clicked.
 *
 * Two modes:
 *
 *  1. **Navigation mode (default)** — sets `dialog: true` on the serialized
 *     payload. The frontend appends `?_dialog=1` to the URL and navigates
 *     via Inertia; the destination Screen renders inside a Dialog.
 *
 *  2. **Inline form mode** — when components are attached via
 *     `->form([...])`, the modal carries the form schema inline and posts
 *     to the action's own `->url()` / `->method()`. The frontend opens a
 *     local Dialog hosting the form, submits via Inertia (so redirects /
 *     flashes work), and closes on success — no extra GET round-trip.
 *     Any darejer component (TextInput, Combobox, DatePicker, Textarea,
 *     Select, …) is supported as an input.
 *
 *     Example:
 *       ModalToggleAction::make('Soft-Close Period')
 *           ->url(route('periods.soft-close', $id))
 *           ->method('POST')
 *           ->form([
 *               Textarea::make('reason')->required(),
 *           ])
 */
class ModalToggleAction extends BaseAction
{
    protected string $modalSize = 'md';

    /** @var array<int, Componentable>|null */
    protected ?array $formComponents = null;

    protected ?string $formTitle = null;

    public function __construct(string $label = '')
    {
        parent::__construct($label);
        $this->method = 'GET';
        $this->variant = 'outline';
        $this->dialog = true;
    }

    public static function make(string $label = ''): static
    {
        return new static($label);
    }

    public function size(string $size): static
    {
        $this->modalSize = $size;

        return $this;
    }

    /**
     * Attach an inline form to the modal. The action's `->url()` /
     * `->method()` become the form's submit target.
     *
     * @param  array<int, Componentable>  $components
     */
    public function form(array $components): static
    {
        $this->formComponents = $components;

        return $this;
    }

    /**
     * Override the dialog title shown when the inline form opens. Defaults
     * to the action label when not set.
     */
    public function formTitle(string $title): static
    {
        $this->formTitle = $title;

        return $this;
    }

    protected function actionType(): string
    {
        return 'ModalToggle';
    }

    protected function actionProps(): array
    {
        return [
            'modalSize' => $this->modalSize,
            'form' => $this->buildInlineForm()?->toArray(),
        ];
    }

    /**
     * Build the inline-form schema from the attached components, using the
     * action's own url/method/label as the form's Save target. Returns
     * null when no components were attached (navigation mode).
     */
    private function buildInlineForm(): ?Form
    {
        if ($this->formComponents === null) {
            return null;
        }

        return Form::make('inline-modal-form')
            ->title($this->formTitle ?? $this->label)
            ->components($this->formComponents)
            ->save($this->url ?? '', $this->method, $this->label);
    }
}
