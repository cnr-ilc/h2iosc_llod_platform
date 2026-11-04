<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between gap-4 flex-wrap">
        <h6 class="text-xl font-medium">{{ trans_choice(':count Resource|:count Resources', $resourceCount, ['count' => $resourceCount]) }}</h6>
        @if (count($resourcesStatus) > 1)
            <div class="flex items-center gap-4">
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model="status" wire:change="updateStatus">
                        <option selected value="">{{ __('All submission statuses') }}</option>
                        @foreach ($resourcesStatus as $status)
                            <option value="{{ $status }}">{{ \App\Enums\ResourceStatusSubmission::from($status)->getLabel() }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>
        @endif
    </div>
    @if ($resourceCount)
        <div class="flex flex-col gap-4">
            @foreach ($resources->items() as $resource)
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
        <x-filament::pagination
            :paginator="$resources"
            :page-options="[5, 10, 20, 50, 100, 'all']"
            current-page-option-property="perPage"
            extreme-links
        />
    @else
        <div class="flex flex-col gap-4">
            <p class="text-center text-gray-500">{{ __('No resources found') }}</p>
        </div>
    @endif
</div>

@script
    <script>
        $wire.on('pageUpdated', (page) => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    </script>
@endscript