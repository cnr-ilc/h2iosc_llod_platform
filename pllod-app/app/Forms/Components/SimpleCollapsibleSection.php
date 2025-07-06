<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Component;

class SimpleCollapsibleSection extends Component
{
    protected string $view = 'forms.components.simple-collapsible-section';

    protected string|\Closure|null $title = null;

    protected bool|\Closure $isOpen = true;

    public static function make(): static
    {
        return app(static::class);
    }

    public function title(string|\Closure|null $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->evaluate($this->title);
    }

    public function open(bool|\Closure $isOpen = true): self
    {
        $this->isOpen = $isOpen;

        return $this;
    }

    public function isOpen(): bool
    {
        return $this->evaluate($this->isOpen);
    }
}
