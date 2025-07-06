@props(['record'])

@php
    use App\Enums\ResourceStatusSubmission;
    $submissionStatus = ResourceStatusSubmission::NOT_SUBMITTED->value;
    if ($record->submission) {
        $submissionStatus = ResourceStatusSubmission::from($record->submission->status)->value;
    }
    $submissionUserNotes = $record->submission?->user_notes;
    $submissionAdminNotes = $record->submission?->admin_notes;
    $submissionUser = $record->submission?->user;
@endphp

<x-filament::section collapsible>
    <x-slot name="heading">
        {{ __('Resource Publication') }}
    </x-slot>
    <div class="flex flex-col gap-8">
        <p class="text-gray-900">
            {{ __('To publish this resource on the PLLOD Production Platform, you must first submit it to ILC admins for approval. You will then be updated on the status of your submission via notification and email.') }}
        </p>
        <x-resource.status.submission :resource="$record" :label="__('Current submission status')"
            labelSize="base" labelColor="text-gray-600" />

        @if($submissionStatus === ResourceStatusSubmission::PENDING->value && !empty($submissionUserNotes))
            <div class="flex gap-2 items-start">
                <div class="text-gray-600 flex-shrink-0">
                    {{ __('User\'s note:') }}
                </div>
                <p class="max-w-4xl">
                    {{ "“{$submissionUserNotes}”" }}
                </p>
            </div>
        @endif

        @if ($submissionStatus === ResourceStatusSubmission::PENDING->value)
            <hr class="w-full border-gray-200">
            <div class="flex items-center justify-center">
                <h6 class="font-semibold">{{ __('Your feedback') }}</h6>
            </div>
            <div class="max-w-5xl mx-auto container">
                <livewire:resource-approvation :resource="$record" />
            </div>
        @endif

        @if (in_array($submissionStatus, [ResourceStatusSubmission::APPROVED->value, ResourceStatusSubmission::REJECTED->value]) && !empty($submissionAdminNotes))
            <div class="flex gap-2 items-start">
                <div class="text-gray-600 flex-shrink-0">
                    {{ __('ILC Admin feedback:') }}
                </div>
                <p class="max-w-4xl">
                    {{ "“{$submissionAdminNotes}”" }}
                </p>
            </div>
        @endif

        @if($submissionStatus === ResourceStatusSubmission::APPROVED->value)
            <p class="text-center text-gray-700">
                {{ __('This submission has been approved and published on the PLLOD Production Platform.') }}
            </p>
        @endif
    </div>
</x-filament::section>