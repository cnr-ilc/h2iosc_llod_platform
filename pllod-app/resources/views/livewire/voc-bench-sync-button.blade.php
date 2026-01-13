@props(['file'])

<div>
    <x-filament::modal id="{{ $this->getSyncModalId() }}" width="lg">
        <x-slot name="trigger" :disabled="$this->isButtonDisabled()">
            <x-filament::button size="lg" outlined icon="heroicon-s-arrow-path" wire:click="openVocBenchSyncModal"
                wire:loading.attr="disabled" wire:target="openVocBenchSyncModal" :disabled="$this->isButtonDisabled()"
                wire:loading.class="opacity-50 cursor-not-allowed">
                {{ __('Sync with VocBench') }}
            </x-filament::button>
        </x-slot>
        <div class="flex items-center justify-center">
            <h2 class="text-xl font-bold">
                {{ __('Sync with VocBench') }}
            </h2>
        </div>
        <p class="text-center">
            <span class="font-bold text-warning-800">{{ __('Warning') }}:</span>
            {{ __('if you proceed to sync the file with VocBench, the file will be invalidated and you will no
            longer be able to submit the resource.') }}
        </p>
        <hr class="w-full border-gray-200">
        <x-slot name="footerActions">
            <div class="flex flex-col md:flex-row items-center gap-4 w-full">
                <x-filament::button class="w-full md:w-1/2" outlined type="button" size="lg"
                    wire:click="closeVocBenchSyncModal" wire:loading.attr="disabled" wire:target="openVocBenchSyncModal"
                    wire:loading.class="opacity-50 cursor-not-allowed">
                    {{ __('Cancel') }}
                </x-filament::button>
                <x-filament::button color="primary" size="lg"
                    class="w-full md:w-1/2 bg-button-gradient hover:bg-button-gradient-hover"
                    wire:click="syncWithVocBench" wire:loading.attr="disabled" wire:target="openVocBenchSyncModal"
                    wire:loading.class="opacity-50 cursor-not-allowed">
                    {{ __('Proceed to sync') }}
                </x-filament::button>
            </div>
        </x-slot>
    </x-filament::modal>
</div>