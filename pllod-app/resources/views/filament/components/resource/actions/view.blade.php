@props(['resource'])

@php
    $showIfStatus = [\App\Enums\ResourceStatusImport::IMPORTED->value];
    $importStatus = \App\Enums\ResourceStatusImport::from($resource->status_import);
    $resourceUrl = \App\Filament\User\Resources\ResourceResource::getUrl('view', ['record' => $resource->id]);
@endphp

@if (in_array($importStatus->value, $showIfStatus))
    <x-filament::button type="button" size="sm" tag="a" wire:navigate :href="$resourceUrl"
        class="bg-button-gradient hover:bg-button-gradient-hover">
        {{ __('filament.resources.actions.view') }}
    </x-filament::button>
@endif
