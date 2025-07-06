@props(['record'])

@php
    $submissionAudits = $record->submission?->audits()->with('user')->orderBy('created_at', 'desc')->get()->map(fn($audit): array => [...$audit->toArray(), 'type' => 'RESOURCE_SUBMISSION', 'modified' => $audit->getModified()]) ?? collect([]);
    $resourceAudits = $record->audits()->with('user')->orderBy('created_at', 'desc')->get()->map(fn($audit): array => [...$audit->toArray(), 'type' => 'RESOURCE_IMPORT', 'modified' => $audit->getModified()]) ?? collect([]);

    $audits = $submissionAudits->merge($resourceAudits)->sortByDesc('created_at');
@endphp

@if (!empty($audits))
<x-filament::section collapsible>
    <x-slot name="heading">
        {{ __('Resource status log') }}
    </x-slot>
    <div class="flex flex-col gap-4">
        @foreach ($audits as $audit)
            @php
                $auditModified = $audit['modified'];
            @endphp

            <div class="flex flex-col gap-4 p-4 border rounded-md">
                <p class="font-semibold">
                    @if ($audit['user']['id'] === auth()->user()->id)
                        {{ __('You') }}
                    @else
                        {{ $audit['user']['name'] }}
                        {{ $audit['user']['last_name'] }}
                    @endif
                    @switch($audit['type'])
                        @case('RESOURCE_SUBMISSION')
                            @switch(\App\Enums\ResourceStatusSubmission::from($auditModified['status']['new'])->value)
                                @case(\App\Enums\ResourceStatusSubmission::PENDING->value)
                                    {{ __('submitted this resource for approval') }}
                                    @break
                                @case(\App\Enums\ResourceStatusSubmission::APPROVED->value)
                                    {{ __('approved this resource') }}
                                    @break
                                @case(\App\Enums\ResourceStatusSubmission::REJECTED->value)
                                    {{ __('rejected this resource') }}
                                    @break
                            @endswitch
                            @break
                        @case('RESOURCE_IMPORT')
                            {{ __('imported this resource') }}
                            @break
                    @endswitch
                </p>
                <p class="text-sm text-gray-500">
                    {{ Carbon\Carbon::parse($audit['created_at'])->diffForHumans() }}
                </p>
                @switch($audit['type'])
                    @case('RESOURCE_SUBMISSION')
                        <x-resource.status.submission :status="\App\Enums\ResourceStatusSubmission::from($auditModified['status']['new'])->value" />
                        @if (isset($auditModified['user_notes']['new']) && $auditModified['user_notes']['new'])
                            <p class="text-gray-700 max-w-4xl">
                                {{ __('“:user_notes”', ['user_notes' => $auditModified['user_notes']['new']]) }}
                            </p>
                        @endif
                        @if (isset($auditModified['admin_notes']['new']) && $auditModified['admin_notes']['new'])
                            <p class="text-gray-700 max-w-4xl">
                                {{ __('“:admin_notes”', ['admin_notes' => $auditModified['admin_notes']['new']]) }}
                            </p>
                        @endif
                        @break
                    @case('RESOURCE_IMPORT')
                        <x-resource.status.submission :status="\App\Enums\ResourceStatusSubmission::NOT_SUBMITTED->value" />
                        @break
                @endswitch
            </div>
        @endforeach
        </div>
    </x-filament::section>
@endif