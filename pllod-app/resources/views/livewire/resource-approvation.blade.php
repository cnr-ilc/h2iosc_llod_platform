<div>
    <form wire:submit="submit" autocomplete="off" class="fi-form grid gap-y-6">
        <div class="flex flex-col gap-2">
            {{ $this->form }}
            <p class="text-sm text-gray-500">
                {{ '(' . __('Max :max words', ['max' => $this->textAreaMaxLength]) . ')' }}
            </p>
        </div>
        <div class="flex justify-center items-center gap-4">
            <x-filament::button type="button" size="lg" wire:click="resetForm" outlined wire:loading.attr="disabled"
                wire:target="resetForm,submit">
                {{ __('Reset') }}
            </x-filament::button>
            <x-filament::modal :close-button="false" id="{{ $this->submitResourceModalId }}" width="xl">
                <x-slot name="trigger">
                    <x-filament::button color="primary" size="lg"
                        class="bg-button-gradient hover:bg-button-gradient-hover">
                        {{ __('Submit resource for approval') }}
                    </x-filament::button>
                </x-slot>
                <div class="flex items-center justify-center">
                    <h2 class="text-xl font-bold">
                        {{ __('Submit resource for approval') }}
                    </h2>
                </div>
                <p class="text-center">
                    {{ __('You are about to request ILC admins for approval on this resource’s publication. Would you like to proceed?') }}
                </p>
                <hr class="w-full border-gray-200">
                <x-slot name="footerActions">
                    <div class="flex flex-col md:flex-row items-center gap-4 w-full">
                        <x-filament::button class="w-full md:w-1/2" outlined type="button" size="lg"
                            wire:click="closeSubmitResourceModal" wire:loading.attr="disabled"
                            wire:target="closeSubmitResourceModal,submit"
                            wire:loading.class="opacity-50 cursor-not-allowed">
                            {{ __('Cancel') }}
                        </x-filament::button>
                        <x-filament::button color="primary" size="lg"
                            class="w-full md:w-1/2 bg-button-gradient hover:bg-button-gradient-hover" wire:click="submit"
                            wire:loading.attr="disabled" wire:target="closeSubmitResourceModal,submit"
                            wire:loading.class="opacity-50 cursor-not-allowed">
                            {{ __('Proceed to submit') }}
                        </x-filament::button>
                    </div>
                </x-slot>
            </x-filament::modal>
        </div>
    </form>

    <x-filament-actions::modals />
</div>