@php
    $columns = $this->getColumns();
    $heading = $this->getHeading();
    $description = $this->getDescription();
    $hasHeading = filled($heading);
    $hasDescription = filled($description);
    $showManageUsersResourceButton = $this->showManageUsersResourceButton();
    $manageUsersResourceButtonURL = $this->getManageUsersResourceButtonURL();
@endphp

<x-filament-widgets::widget class="fi-wi-stats-overview grid gap-y-4">
    @if ($hasHeading || $hasDescription)
        <div class="fi-wi-stats-overview-header grid gap-y-1">
            @if ($hasHeading)
                <h3
                    class="fi-wi-stats-overview-header-heading col-span-full text-base font-semibold leading-6 text-gray-950 dark:text-white">
                    {{ $heading }}
                </h3>
            @endif

            @if ($hasDescription)
                <p
                    class="fi-wi-stats-overview-header-description overflow-hidden break-words text-sm text-gray-500 dark:text-gray-400">
                    {{ $description }}
                </p>
            @endif
        </div>
    @endif

    <div @if ($pollingInterval = $this->getPollingInterval()) wire:poll.{{ $pollingInterval }} @endif @class([
        'fi-wi-stats-overview-stats-ctn grid gap-6',
        'md:grid-cols-1' => $columns === 1,
        'md:grid-cols-2' => $columns === 2,
        'md:grid-cols-3' => $columns === 3,
        'md:grid-cols-2 xl:grid-cols-4' => $columns === 4,
    ])>
        @foreach ($this->getCachedStats() as $stat)
            {{ $stat }}
        @endforeach
        @if ($showManageUsersResourceButton && $manageUsersResourceButtonURL)
            <a href="{{ $manageUsersResourceButtonURL }}" wire:navigate
                class="relative rounded-xl bg-button-gradient hover:bg-button-gradient-hover h-full p-6 ring-1 ring-gray-950/5 shadow-md transition-shadow duration-300 hover:cursor-pointer flex flex-col gap-y-2 hover:shadow-primary-600">
                <div class="flex items-center justify-between gap-x-3">
                    <h5 class="text-base font-bold leading-6 text-white">
                        {{ __('filament.widgets.admin-resources-stats-overview.manage-users-resources') }}
                    </h5>
                    <x-heroicon-m-arrow-up-right class="h-6 w-6 text-white" />
                </div>
                <p class="text-sm text-white">
                    {{ __('filament.widgets.admin-resources-stats-overview.manage-users-resources-description') }}
                </p>
            </a>
        @endif
    </div>
</x-filament-widgets::widget>
