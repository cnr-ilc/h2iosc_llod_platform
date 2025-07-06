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
        <p>
            {{ __('To publish this resource on the PLLOD Production Platform, you must first submit it to ILC admins for approval. You will then be updated on the status of your submission via notification and email.') }}
        </p>
        <x-resource.status.submission :resource="$record" :label="__('Current submission status')"
            labelSize="base" labelColor="text-gray-600" />

        @if ($submissionStatus === ResourceStatusSubmission::NOT_SUBMITTED->value)
            <hr class="w-full border-gray-200">
            <div class="flex items-center justify-center flex-col gap-4">
                @if (!$record->can_be_submitted)
                    <div class="p-5 bg-primary-50 rounded-full items-center justify-center flex">
                        <x-heroicon-o-information-circle class="w-6 h-6 text-primary-600" />
                    </div>
                @endif
                <h6 class="font-semibold">{{ __('Approval request for resource publication') }}</h6>
                @if (!$record->can_be_submitted)
                    <p class="container max-w-5xl mx-auto text-center">
                        {{ __('This resource cannot be published on the PLLOD Production Platform as it contains edited files. If you wish to publish this resource, you will need to download and re-deposit all edited files on the CLARIN Repository.') }}
                    </p>
                @endif
            </div>
            <div class="max-w-5xl mx-auto container relative">
                <livewire:resource-publication :resource="$record" />
                @if (!$record->can_be_submitted)
                    <div class="absolute top-0 right-0 w-full h-full bg-white opacity-25 cursor-not-allowed"></div>
                @endif
            </div>
        @endif

        @if($submissionStatus === ResourceStatusSubmission::PENDING->value && !empty($submissionUserNotes))
            <div class="flex gap-2 items-start">
                <div class="text-gray-600 flex-shrink-0">
                    {{ $submissionUser->id === auth()->user()->id ? __('Your note:') : __(':userName :userLastName\'s note:', ['userName' => $submissionUser->name, 'userLastName' => $submissionUser->last_name]) }}
                </div>
                <p class="max-w-4xl">
                    {{ "“{$submissionUserNotes}”" }}
                </p>
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
                {{ __('Your submission has been approved and published on the PLLOD Production Platform.') }}
            </p>
        @endif
    </div>
</x-filament::section>