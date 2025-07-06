@props(['resource'])

@php
    $resourceUrl = \App\Filament\Admin\Resources\ResourceResource::getUrl('view', ['record' => $resource->id]);
@endphp

<x-filament::button type="button" size="sm" tag="a" wire:navigate :href="$resourceUrl"
class="bg-button-gradient hover:bg-button-gradient-hover">
{{ __('filament.resources.actions.view') }}
</x-filament::button>
