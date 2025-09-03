@props(['file'])

@php
$isSyncButtonDisabled = $file->validation_status !== \App\Enums\GeneralStatus::SUCCESS->value ||
!$file->can_be_opened_in_vocbench ||
$file->quality_assessment_status === \App\Enums\GeneralStatus::RUNNING->value ||
$file->is_synchronising ||
empty($file->graphdb_repository_name);
@endphp

<div>
    <x-filament::button size="lg" outlined icon="heroicon-s-arrow-path" wire:click="syncWithVocBench"
        wire:loading.attr="disabled" wire:target="syncWithVocBench" :disabled="$isSyncButtonDisabled">
        <span wire:loading.remove wire:target="syncWithVocBench">
            {{ __('Sync with VocBench') }}
        </span>
        <span wire:loading wire:target="syncWithVocBench">
            {{ __('Syncing...') }}
        </span>
    </x-filament::button>
</div>