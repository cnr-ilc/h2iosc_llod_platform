<section class="flex flex-col gap-8">
    <x-filament-panels::form wire:submit="submit" autocomplete="off">
        {{ $this->form }}
        <div class="flex justify-end gap-4">
            <x-filament::button type="button" size="md" wire:click="resetForm" outlined wire:loading.attr="disabled"
                wire:target="resetForm,submit">
                {{ __('filament.pages.import-resource.form.reset') }}
            </x-filament::button>
            <x-filament::button type="submit" size="md" class="bg-button-gradient hover:bg-button-gradient-hover"
                wire:loading.attr="disabled" wire:target="resetForm,submit">
                {{ __('filament.pages.import-resource.form.submit') }}
            </x-filament::button>
        </div>
    </x-filament-panels::form>
    <hr class="w-full text-gray-200">
    @if ($this->loading)
        <h3 class="text-xl font-bold text-gray-900 leading-none">
            {{ __('filament.pages.import-resource.searching') }}
        </h3>
    @endif
    @if (!$this->loading && $this->results && $this->results->isNotEmpty())
        <div class="flex flex-col gap-4">
            <h3 class="text-xl font-bold text-gray-900 leading-none">
                {{ __('filament.pages.import-resource.results-found', ['count' => $this->results->count()]) }}
            </h3>
            <div class="flex flex-col gap-4">
                @foreach ($this->results as $result)
                    <x-filament::resource.item :resource="$result">
                        <x-slot:actions>
                            <x-filament::button type="button" size="sm" :disabled="$result->files->isEmpty()"
                                class="bg-button-gradient hover:bg-button-gradient-hover" wire:click="importResource">
                                {{ __('filament.resources.actions.import') }}
                            </x-filament::button>
                        </x-slot>
                    </x-filament::resource.item>
                @endforeach
            </div>
        </div>
    @endif
</section>
