<?php

namespace Darejer\Components;

use BackedEnum;
use Darejer\Support\EnumOptions;

class RadioGroup extends BaseComponent
{
    protected array $options = [];

    protected string $layout = 'vertical';  // vertical | horizontal

    protected bool $disabled = false;

    /**
     * @param  array<string, string>|class-string<BackedEnum>  $options
     */
    public function options(array|string $options): static
    {
        $this->options = EnumOptions::labels($options);

        return $this;
    }

    public function horizontal(): static
    {
        $this->layout = 'horizontal';

        return $this;
    }

    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;

        return $this;
    }

    protected function componentType(): string
    {
        return 'RadioGroup';
    }

    protected function componentProps(): array
    {
        $normalized = collect($this->options)
            ->map(fn ($label, $value) => ['value' => (string) $value, 'label' => $label])
            ->values()
            ->all();

        return [
            'options' => $normalized,
            'layout' => $this->layout,
            'disabled' => $this->disabled ?: null,
        ];
    }
}
