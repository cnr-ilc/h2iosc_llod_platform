<?php

namespace App\Forms\Components;

use Closure;
use Filament\Schemas\Components\Component;
use Illuminate\Contracts\Support\Htmlable;

class SimpleFieldset extends Component
{
    protected string $view = 'forms.components.simple-fieldset';

    protected string|Closure|Htmlable|null $legend = null;

    public static function make(): static
    {
        return app(static::class);
    }

    public function legend(string|Closure|Htmlable|null $label): static
    {
        $this->legend = $label;

        return $this;
    }

    public function getLegend(): ?string
    {
        return $this->evaluate($this->legend);
    }
}
