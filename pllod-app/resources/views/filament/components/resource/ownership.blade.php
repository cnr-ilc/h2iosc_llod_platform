@props(['record'])

<x-filament::section collapsible>
    <x-slot name="heading">
        {{ __('Resource Ownership') }}
    </x-slot>
    <div class="flex gap-4">
        <div class="flex flex-col gap-2 w-1/2">
            <h6 class="text-md font-semibold">
                {{ __('Owned by') }}
            </h6>
            <ul class="flex flex-col gap-2">
                @foreach ($record->users as $user)
                    <li>
                        {{ $user->name }} {{ $user->last_name }}
                    </li>
                @endforeach
            </ul>
        </div>
        {{-- <div class="flex flex-col gap-2 w-1/2">
            <h6 class="text-md font-semibold">
                {{ __('Shared to') }}
            </h6>
            <ul class="flex flex-col gap-2">
                
            </ul>
        </div> --}}
    </div>
</x-filament::section>