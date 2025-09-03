@props(['file'])

<div>
    <x-filament::button size="lg" class="bg-button-gradient hover:bg-button-gradient-hover"
        icon="heroicon-s-pencil-square" loading.attr="disabled" :disabled="$this->isButtonDisabled()"
        wire:click="openVocBench" wire:loading.attr="disabled" wire:target="openVocBench"
        wire:loading.class="opacity-50 cursor-not-allowed">
        {{ __('Open file with VocBench') }}
    </x-filament::button>
    @script
    <script>
        $wire.on('pllod-vocbench-redirect-uri-{{ $file->id }}', (event) => {
                window.open(event[0], '_blank');
            });
    </script>
    @endscript
</div>