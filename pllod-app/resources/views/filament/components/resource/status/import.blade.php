@props(['resource'])

@php
    $showIfStatus = [\App\Enums\ResourceStatusImport::PENDING->value, \App\Enums\ResourceStatusImport::FAILED->value];
    $importStatus = \App\Enums\ResourceStatusImport::from($resource->status_import);
    $description = $importStatus->getDescription();
@endphp

@if (in_array($importStatus->value, $showIfStatus))
    <x-filament::badge @class([
        'text-warning-800',
        'bg-warning-100',
        'cursor-pointer',
        'rounded-xl',
        'px-2',
        'py-1',
    ]) :tooltip="$description" icon="heroicon-o-information-circle"
        icon-position="after" color="warning-800">
        {{ $importStatus->getLabel() }}
    </x-filament::badge>
@endif
