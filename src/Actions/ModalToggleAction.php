<?php

namespace Darejer\Actions;

use Darejer\Forms\Form;

/**
 * Action that opens a dialog/modal when clicked.
 *
 * Two modes:
 *
 *  1. **Navigation mode (default)** — sets `dialog: true` on the serialized
 *     payload. The frontend appends `?_dialog=1` to the URL and navigates
 *     via Inertia; the destination Screen renders inside a Dialog.
 *
 *  2. **Inline form mode** — when a `Form` is attached via `->form(...)`,
 *     the modal carries the form schema (title, components, actions,
 *     record) inline. The frontend opens a local Dialog hosting the form,
 *     submits to the form's Save action URL via Inertia, and closes the
 *     dialog on success — no extra GET round-trip needed. Any darejer
 *     component (TextInput, Combobox, DatePicker, Textarea, Select, …) is
 *     supported as a form input.
 */
class ModalToggleAction extends BaseAction
{
    protected string $modalSize = 'md';

    protected ?Form $form = null;

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
     * Attach a `Form` to render inline inside the modal. The form must have
     * a Save action configured via `->save($url, $method, $label)` — that is
     * the endpoint the dialog posts to on submit.
     */
    public function form(Form $form): static
    {
        $this->form = $form;

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
            'form' => $this->form?->toArray(),
        ];
    }
}
