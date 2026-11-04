{{-- Card-link mostrata accanto agli Stat negli stats overview widget (vedi getSectionContentComponent()) --}}
<a href="{{ $url }}" wire:navigate
    class="relative rounded-xl bg-button-gradient hover:bg-button-gradient-hover h-full p-6 ring-1 ring-gray-950/5 shadow-md transition-shadow duration-300 hover:cursor-pointer flex flex-col gap-y-2 hover:shadow-primary-600">
    <div class="flex items-center justify-between gap-x-3">
        <h5 class="text-base font-bold leading-6 text-white">
            {{ $heading }}
        </h5>
        <x-heroicon-m-arrow-up-right class="h-6 w-6 text-white" />
    </div>
    <p class="text-sm text-white">
        {{ $description }}
    </p>
</a>
