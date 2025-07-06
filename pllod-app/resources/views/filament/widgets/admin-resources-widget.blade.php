<x-filament-widgets::widget>
    <div @if ($pollingInterval = $this->getPollingInterval()) wire:poll.{{ $pollingInterval }} @endif @class(['hidden' => $resources->isEmpty()])>
        <div class="flex flex-col gap-6">
            <hr class="w-full text-gray-200 mt-3 mb-3 md:mt-6 md:mb-6">
            <div class="flex justify-between items-center gap-3 min-h-9">
                <h3 class="text-2xl font-bold text-gray-900 leading-none">
                    {{ __('filament.widgets.admin-resources.title') }}
                </h3>
            </div>
            <div class="flex flex-col gap-4">
                @foreach ($resources as $resource)
                    <x-filament::resource.admin.item :resource="$resource" :key="$resource->id" >
                        <x-slot:actions>
                            <x-filament::resource.admin.actions.view :resource="$resource" />
                        </x-slot>
                        <x-slot:footer>
                            <div class="flex justify-between items-center gap-3">
                                <x-filament::resource.status.submission :resource="$resource" />
                                <x-filament::resource.admin.status.submitted :resource="$resource" />
                            </div>
                        </x-slot>
                    </x-filament::resource.admin.item>
                @endforeach
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
