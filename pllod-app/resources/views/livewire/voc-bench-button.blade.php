@props(['file'])

@php
$isButtonDisabled = $file->validation_status !== \App\Enums\GeneralStatus::SUCCESS->value ||
!$file->can_be_opened_in_vocbench || $file->quality_assessment_status === \App\Enums\GeneralStatus::RUNNING->value;
@endphp

<div>
    <x-filament::modal id="voc-bench-modal-{{ $file->id }}" width="lg">
        <x-slot name="trigger" :disabled="$isButtonDisabled">
            <x-filament::button size="lg" class="bg-button-gradient hover:bg-button-gradient-hover"
                icon="heroicon-s-pencil-square" loading.attr="disabled" :disabled="$isButtonDisabled">
                {{ __('Open file with VocBench') }}
            </x-filament::button>
        </x-slot>
        <div class="flex items-center justify-center">
            <h2 class="text-xl font-bold">
                {{ __('Open file with VocBench') }}
            </h2>
        </div>
        <p class="text-center">
            <span class="font-bold text-warning-800">{{ __('Warning') }}:</span>
            {{ __('Resources with edited or re-uploaded files cannot be published on the PLLOD Production Platform. If
            you wish to publish a resource, you will need to download and re-deposit all edited files on the CLARIN
            Repository.') }}
        </p>
        <hr class="w-full border-gray-200">
        <x-slot name="footerActions">
            <div class="flex flex-col md:flex-row items-center gap-4 w-full">
                <x-filament::button class="w-full md:w-1/2" outlined type="button" size="lg"
                    wire:click="closeVocBenchModal" wire:loading.attr="disabled" wire:target="closeVocBenchModal"
                    wire:loading.class="opacity-50 cursor-not-allowed">
                    {{ __('Cancel') }}
                </x-filament::button>
                <x-filament::button color="primary" size="lg"
                    class="w-full md:w-1/2 bg-button-gradient hover:bg-button-gradient-hover" wire:click="openVocBench"
                    wire:loading.attr="disabled" wire:target="openVocBench"
                    wire:loading.class="opacity-50 cursor-not-allowed">
                    {{ __('Proceed to edit') }}
                </x-filament::button>
            </div>
        </x-slot>
    </x-filament::modal>
    @script
    <script>
        $wire.on('pllod-vocbench-redirect-uri-{{ $file->id }}', (event) => {
                window.open(event[0], '_blank');
            });
    </script>
    @endscript
</div>