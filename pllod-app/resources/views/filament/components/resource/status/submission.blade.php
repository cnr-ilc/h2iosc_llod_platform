@props(['resource' => null, 'status' => null, 'label' => null, 'labelSize' => 'sm', 'labelColor' => 'text-gray-900'])

@php
    $submissionStatus = \App\Enums\ResourceStatusSubmission::NOT_SUBMITTED->value;
    if ($resource && $resource->submission) {
        $submissionStatus = \App\Enums\ResourceStatusSubmission::from($resource->submission->status)->value;
    }
    if ($status) {
        $submissionStatus = $status;
    }
@endphp

<div class="flex items-center gap-2">
    <p class="text-{{ $labelSize }} {{ $labelColor }}">
        {{ $label ?? __('filament.resources.status.submission.label') }}:
    </p>
    <div @class([
        'text-sm font-bold flex items-center gap-1',
        'text-gray-700' =>
            $submissionStatus === \App\Enums\ResourceStatusSubmission::NOT_SUBMITTED->value,
        'text-warning-800' =>
            $submissionStatus === \App\Enums\ResourceStatusSubmission::PENDING->value,
        'text-danger-700' =>
            $submissionStatus === \App\Enums\ResourceStatusSubmission::REJECTED->value,
        'text-success-700' =>
            $submissionStatus === \App\Enums\ResourceStatusSubmission::APPROVED->value,
    ])>
        @switch($submissionStatus)
            @case(\App\Enums\ResourceStatusSubmission::NOT_SUBMITTED->value)
                <x-heroicon-o-no-symbol class="w-5 h-5" />
            @break

            @case(\App\Enums\ResourceStatusSubmission::PENDING->value)
                <x-heroicon-o-arrow-path class="w-5 h-5" />
            @break

            @case(\App\Enums\ResourceStatusSubmission::REJECTED->value)
                <x-heroicon-o-x-mark class="w-5 h-5" />
            @break

            @case(\App\Enums\ResourceStatusSubmission::APPROVED->value)
                <x-heroicon-o-check class="w-5 h-5" />
            @break
        @endswitch
        <span>
            {{ \App\Enums\ResourceStatusSubmission::from($submissionStatus)->getLabel() }}
        </span>
    </div>
</div>
