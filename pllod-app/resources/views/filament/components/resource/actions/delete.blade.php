@props(['resource'])

@php
    $showIfStatus = [\App\Enums\ResourceStatusImport::FAILED->value];
    $importStatus = \App\Enums\ResourceStatusImport::from($resource->status_import);
@endphp

@if (in_array($importStatus->value, $showIfStatus))
    <x-filament::modal id="delete-resource-{{ $resource->id }}" width="md" alignment="center" :close-by-clicking-away="false">

        <x-slot name="trigger">
            <button
                class="text-danger-700 hover:text-danger-600 flex items-center gap-x-1 text-sm font-medium flex-wrap">
                <x-heroicon-o-x-mark class="w-4 h-4" />
                <span>
                    {{ __('filament.resources.actions.delete') }}
                </span>
            </button>
        </x-slot>

        <x-slot name="header">
            <div class="flex flex-col items-center">
                <div class="mb-5 flex items-center justify-center">
                    <div class="rounded-full bg-danger-50 p-3">
                        <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-danger-600" />
                    </div>
                </div>
                <div class="text-base font-semibold leading-6 text-gray-950">
                    {{ __('Remove resource from profile') }}
                </div>
            </div>
            <div class="text-sm text-gray-500 text-center mt-2">
                <p>{{ __('Are you sure you would like to do this?') }}</p>
            </div>
        </x-slot>

        <x-slot name="footer">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-filament::button type="button" outlined class="ring-gray-950/10"
                    x-on:click="$dispatch('close-modal', { id: 'delete-resource-{{ $resource->id }}' })" size="md">
                    {{ __('Cancel') }}
                </x-filament::button>

                <x-filament::button type="button"
                    wire:click="deleteResource({{ $resource->id }}); $dispatch('close-modal', { id: 'delete-resource-{{ $resource->id }}' })"
                    class="bg-danger-600 hover:bg-danger-500 text-white" size="md">
                    {{ __('Yes, delete it') }}
                </x-filament::button>
            </div>
        </x-slot>
    </x-filament::modal>
@endif
