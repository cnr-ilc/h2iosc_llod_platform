@props(['resource', 'status' => null, 'actions' => null, 'footer' => null])

<div class="flex flex-col gap-4 border border-gray-200 rounded-xl p-6 bg-white">
    <div class="flex gap-4 items-start flex-col">
        <div class="flex gap-6 justify-between items-start w-full">
            <div class="flex flex-col gap-1">
                <p class="text-sm text-gray-600">{{ $resource->type }}</p>
                <h2 class="text-lg font-bold text-primary-900">{{ $resource->name }}</h2>
            </div>
            @if ($status || $actions)
                <div class="flex items-center gap-2">
                    @if ($status)
                        {{ $status }}
                    @endif
                    @if ($actions)
                        {{ $actions }}
                    @endif
                </div>
            @endif
        </div>
    </div>
    <hr class="w-full text-gray-200">
    <div class="flex flex-col gap-2">
        @if (filled($resource->authors))
            <p>
                <span class="text-gray-600">{{ __('Author(s)') }}:</span>
                {{ $resource->authors }}
            </p>
        @endif
        @if (filled($resource->publisher))
            <p>
                <span class="text-gray-600">{{ __('Publisher') }}:</span>
                {{ $resource->publisher }}
            </p>
        @endif
        @if (filled($resource->issued_date))
            <p>
                <span class="text-gray-600">{{ __('Issued date') }}:</span>
                {{ $resource->issued_date }}
            </p>
        @endif
    </div>
    <div class="flex items-center gap-2">
        <p class="text-gray-900 text-sm">{{ __('Available file(s)') }}:</p>
        <span
            class="font-bold text-primary-900 bg-brand-100 rounded-full inline-flex items-center justify-center leading-none p-2 min-w-7 min-h-5">
            {{ $resource->files->count() ?? 0 }}
        </span>
    </div>
    @if ($footer)
        <hr class="w-full text-gray-200">
        {{ $footer }}
    @endif
</div>
