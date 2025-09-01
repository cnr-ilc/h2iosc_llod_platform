@props(['file'])

<x-filament::section collapsible heading="{!! $file->name !!}">
    <div class="flex flex-col gap-8">
        <div class="flex flex-col gap-4">
            <h5 class="text-lg font-bold">{{ __('File metadata') }}</h5>
            <div class="flex gap-2 items-center text-base">
                <p class="text-gray-600">{{ __('Original name') }}:</p>
                <p class="text-gray-900">{{ $file->name }}</p>
            </div>
            <div class="flex gap-2 items-center text-base">
                <p class="text-gray-600">{{ __('Size') }}:</p>
                <p class="text-gray-900">{{ $file->size }}</p>
            </div>
            @if ($file->description)
                <div class="flex gap-2 items-center text-base">
                    <p class="text-gray-600">{{ __('Description') }}:</p>
                    <p class="text-gray-900">{{ $file->description }}</p>
                </div>
            @endif
            <div class="flex gap-2 items-center text-base">
                <p class="text-gray-600">{{ __('MD5') }}:</p>
                <p class="text-gray-900">{{ $file->hash_md5 }}</p>
            </div>
        </div>
        @if (!empty($file->validation_status))
            <x-file.health :file="$file" />
        @endif
        <div class="flex gap-4 items-center justify-center flex-wrap">
            <x-filament::button
                size="lg"
                outlined
                tag="a" 
                href="{{ route('file.download', $file->id) }}"
                target="_blank"
                icon="heroicon-o-document-arrow-down">
                {{ __('Download file') }}
            </x-filament::button>
            {{ ($this->reuploadAction)(['currentFileId' => $file->id]) }}
            <livewire:voc-bench-sync-button :file="$file" />
            <livewire:voc-bench-button :file="$file" />
        </div>
        <hr class="w-full border-gray-200">
        <x-file.quality :file="$file" />
    </div>
    <x-filament-actions::modals />
</x-filament::section>
