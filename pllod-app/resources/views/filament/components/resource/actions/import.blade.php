@props(['resource'])

<x-filament::button type="button" size="sm" wire:click="importResource({{ $resource->id }})"
    class="bg-button-gradient hover:bg-button-gradient-hover">
    {{ __('filament.resources.actions.import') }}
</x-filament::button>
