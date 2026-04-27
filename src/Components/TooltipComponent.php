<?php

namespace Darejer\Components;

class TooltipComponent extends BaseComponent
{
    protected string $content = '';

    protected string $trigger = '';

    protected string $side = 'top';

    protected string $triggerType = 'text';

    protected ?string $iconName = null;

    public function content(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function trigger(string $trigger): static
    {
        $this->trigger = $trigger;

        return $this;
    }

    public function icon(string $iconName): static
    {
        $this->triggerType = 'icon';
        $this->iconName = $iconName;

        return $this;
    }

    public function side(string $side): static
    {
        $this->side = $side;

        return $this;
    }

    protected function componentType(): string
    {
        return 'TooltipComponent';
    }

    protected function componentProps(): array
    {
        return [
            'content' => $this->content,
            'trigger' => $this->trigger,
            'side' => $this->side,
            'triggerType' => $this->triggerType,
            'iconName' => $this->iconName,
        ];
    }
}
