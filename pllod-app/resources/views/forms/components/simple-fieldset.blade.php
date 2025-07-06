@php
    $uuid = Str::uuid();
@endphp

<div role="group" {{ $attributes->class(['fi-simple-fieldset flex flex-col gap-y-2']) }}
    aria-labelledby="{{ $uuid }}">
    <legend class="fi-simple-fieldset-legend text-base font-medium" id="{{ $uuid }}">{{ $getLegend() }}</legend>
    <div @class([
        'fi-simple-fieldset-fields-ctn grid gap-4 md:gap-6',
        'md:grid-cols-1' => $columns === 1,
        'md:grid-cols-2' => $columns === 2,
        'md:grid-cols-3' => $columns === 3,
        'md:grid-cols-2 xl:grid-cols-4' => $columns === 4,
    ])>
        {{ $getChildComponentContainer() }}
    </div>
</div>
