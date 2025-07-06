<x-filament-widgets::widget>
    <div @if ($pollingInterval = $this->getPollingInterval()) wire:poll.{{ $pollingInterval }} @endif @class(['hidden' => $resources->isEmpty()])>
        <div class="flex flex-col gap-6">
            <hr class="w-full text-gray-200 mt-3 mb-3 md:mt-6 md:mb-6">
            <div class="flex justify-between items-center gap-3 min-h-9">
                <h3 class="text-2xl font-bold text-gray-900 leading-none">
                    {{ __('filament.widgets.user-resources.title') }}
                </h3>
                @if ($statuses->count() > 1)
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model="statusFilter" wire:change="$refresh">
                            <option value="all" selected>{{ __('All Statuses') }}</option>
                            @foreach ($statuses as $status)
                                <option wire:key="status-{{ $status->value }}" value="{{ $status->value }}">
                                    {{ $status->label }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                @endif
            </div>
            <div class="flex flex-col gap-4">
                @foreach ($resources as $resource)
                    <x-filament::resource.item :resource="$resource" :key="$resource->id">
                        <x-slot:actions>
                            <x-filament::resource.status.import :resource="$resource" />
                            <x-filament::resource.actions.view :resource="$resource" />
                            <x-filament::resource.actions.delete :resource="$resource" />
                        </x-slot>
                        <x-slot:footer>
                            <x-filament::resource.status.submission :resource="$resource" />
                        </x-slot>
                    </x-filament::resource.item>
                @endforeach
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
