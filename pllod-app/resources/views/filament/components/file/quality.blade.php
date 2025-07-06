@props(['file'])

@php
    use App\Enums\GeneralStatus;
    use App\Models\Setting;
@endphp

<div class="flex flex-col gap-8">
    <div class="flex flex-col gap-4">
        <h5 class="text-lg font-semibold">
            {{ __('File Quality') }}
        </h5>
        <div class="flex flex-col gap-4 p-6 rounded-xl border border-gray-200">
            @empty($file->quality_assessment_status)
                <div class="flex justify-center items-center flex-col gap-4">
                    <div class="flex gap-3 items-center p-5 rounded-full bg-primary-100">
                        <x-heroicon-o-x-mark class="w-6 h-6 text-primary-600" />
                    </div>
                    <p class="font-semibold">
                        {{ __('No records found for this file version') }}
                    </p>
                </div>
            @else
                @switch($file->quality_assessment_status)
                    @case(GeneralStatus::RUNNING->value)
                        <div class="flex gap-3 items-center">
                            <div class="flex gap-3 self-start">
                            <x-heroicon-o-exclamation-circle class="w-7 h-7 text-yellow-700" />
                        </div>
                        <div class="flex flex-col gap-2 w-full">
                            <div class="flex gap-2 justify-between items-start flex-wrap">
                                <h5 class="text-lg font-semibold">
                                    {{ __('This file is undergoing quality evaluation.') }}
                                </h5>
                                <div class="text-sm font-semibold text-warning-800 bg-warning-100 px-2 py-1 rounded-full">
                                        {{ __('Evaluation on-going') }}
                                    </div>
                                </div>
                                <p>
                                    {{ __('Check back in a few moments.') }}
                                </p>
                            </div>
                        </div>
                    @break
                @case(GeneralStatus::SUCCESS->value)
                    <div class="flex flex-col gap-2">
                        <div class="flex gap-2 justify-between items-start flex-wrap" x-data="{ tooltip: 'Editing or reuploading a file will delete the existing quality evaluation.' }">
                            <h5 class="text-md font-semibold">
                                {{ __('Automated evaluation results') }}
                            </h5>
                            <x-heroicon-o-information-circle class="w-7 h-7 text-gray-700 cursor-pointer" x-tooltip="tooltip" />
                        </div>
                        @if ($file->quality_assessment_timestamp)
                            <p class="text-sm text-gray-700">
                                {{ $file->quality_assessment_timestamp->diffForHumans() }}
                            </p>
                        @endif
                        @if (!empty($file->quality_score))
                            <div class="flex gap-2 items-center">
                                {{ __('Overall quality score:') }}
                                <x-file.quality-score :score="$file->quality_score['total_score'] ?? 0" />
                            </div>
                        @endif
                    </div>
                    @break
                @case(GeneralStatus::FAILED->value)
                    <div class="flex gap-3 items-center">
                        <div class="flex gap-3 self-start">
                            <x-heroicon-o-exclamation-circle class="w-7 h-7 text-red-700" />
                        </div>
                    </div>
                    @break
                @endswitch
            @endempty
            @if (!empty($file->quality_result) && $file->quality_assessment_status === GeneralStatus::SUCCESS->value)
                <hr class="w-full border-gray-200">
                <x-filament::modal width="7xl" id="quality-report-modal-{{ $file->id }}" sticky-footer sticky-header>
                    <x-slot name="trigger" autofocus="false">
                        <div class="flex gap-1 items-center hover:underline text-brand-600">
                            <x-heroicon-o-eye class="w-5 h-5" />
                            {{ __('View full report') }}
                        </div>
                    </x-slot>

                    <x-slot name="header">
                        <div class="flex justify-between items-center gap-2">
                            <h2 class="text-xl font-medium">
                                {{ __('Automated evaluation results') }}
                            </h2>
                        </div>
                    </x-slot>

                    <div class="flex flex-col gap-8">
                        <div class="flex flex-col gap-4">
                            <div class="flex gap-2 items-center">
                                <h3 class="text-lg font-medium">
                                    {{ __('Overall quality score') }}
                                </h3>
                                <x-file.quality-score :score="$file->quality_score['total_score'] ?? 0" />
                            </div>
                        </div>
                        <div class="flex flex-col gap-4">
                            <div class="flex flex-col gap-2">
                                <h3 class="text-lg font-medium">
                                    {{ __('Individual component scores') }}
                                </h3>
                                <p class="text-gray-600">
                                    {{ __('Note: Recommendations for improvement are automatically generated in case of failing scores.') }}
                                </p>
                            </div>
                            <div class="p-6 rounded-xl border border-gray-200 flex flex-col gap-4">
                                <h4 class="font-semibold">
                                    {{ __('Open license availability') }}
                                </h4>
                                <div class="flex items-center gap-2">
                                    <span class="text-gray-600">
                                        {{ __('Score') }}
                                    </span>
                                    <x-file.quality-score-bool :passed="$file->quality_result['license_info']['is_open_license'] ?? false" />
                                </div>
                            </div>
                            <div class="p-6 rounded-xl border border-gray-200 flex flex-col gap-4">
                                <h4 class="font-semibold">
                                    {{ __('URI validity') }}
                                </h4>
                                <div class="flex items-center gap-2">
                                    <span class="text-gray-600">
                                        {{ __('Score') }}
                                    </span>
                                    <x-file.quality-score :score="$file->quality_score['uri_validity_score'] ?? 0" />
                                </div>
                                <x-common.simple-accordion>
                                    <div class="flex flex-col gap-2">
                                        <div class="flex gap-2 items-center">
                                            <span class="text-gray-600">
                                                {{ __('Total base URIs found:') }}
                                            </span>
                                            <span class="text-gray-900 font-medium">
                                                {{ $file->quality_result['uri_validity']['total_base_uris_found'] ?? 0 }}
                                            </span>
                                        </div>
                                        <div class="flex gap-2 items-center">
                                            <span class="text-gray-600">
                                                {{ __('Total base URIs accessible:') }}
                                            </span>
                                            <span class="text-gray-900 font-medium">
                                                {{ $file->quality_result['uri_validity']['total_base_uris_accessible'] ?? 0 }}
                                            </span>
                                        </div>
                                    </div>
                                </x-common.simple-accordion>
                            </div>
                            <div class="p-6 rounded-xl border border-gray-200 flex flex-col gap-4">
                                <h4 class="font-semibold">
                                    {{ __('External links coverage') }}
                                </h4>
                                <div class="flex items-center gap-2">
                                    <span class="text-gray-600">
                                        {{ __('Score') }}
                                    </span>
                                    <x-file.quality-score :score="$file->quality_score['external_links_score'] ?? 0" />
                                </div>
                                <x-common.simple-accordion>
                                    <div class="flex flex-col gap-2">
                                        <div class="flex gap-2 items-center">
                                            <span class="text-gray-600">
                                                {{ __('External percentage:') }}
                                            </span>
                                            <span class="text-gray-900 font-medium">
                                                {{ $file->quality_result['external_links']['external_percentage'] ?? 0 }}
                                            </span>
                                        </div>
                                        <div class="flex gap-2 items-center">
                                            <span class="text-gray-600">
                                                {{ __('Meets threshold:') }}
                                            </span>
                                            <span class="text-gray-900 font-medium">
                                                {{ $file->quality_result['external_links']['meets_threshold'] ? __('Yes') : __('No') }}
                                            </span>
                                        </div>
                                        <div class="flex gap-2 items-center">
                                            <span class="text-gray-600">
                                                {{ __('External count:') }}
                                            </span>
                                            <span class="text-gray-900 font-medium">
                                                {{ $file->quality_result['external_links']['external_count'] ?? 0 }}
                                            </span>
                                        </div>
                                        <div class="flex gap-2 items-center">
                                            <span class="text-gray-600">
                                                {{ __('Total count:') }}
                                            </span>
                                            <span class="text-gray-900 font-medium">
                                                {{ $file->quality_result['external_links']['total_count'] ?? 0 }}
                                            </span>
                                        </div>
                                    </div>
                                </x-common.simple-accordion>
                            </div>
                            <div class="p-6 rounded-xl border border-gray-200 flex flex-col gap-4">
                                <h4 class="font-semibold">
                                    {{ __('SHACL validation') }}
                                </h4>
                                <div class="flex items-center gap-2">
                                    <span class="text-gray-600">
                                        {{ __('Score') }}
                                    </span>
                                    @if ($file->quality_score['shacl_score'] === null)
                                        <span @class([
                                            'rounded-full px-[10px] py-[2px] flex items-center font-medium',
                                            'bg-warning-100 text-warning-800',
                                        ])>
                                            {{ __('Skipped') }}
                                        </span>
                                    @else
                                        <x-file.quality-score-bool :passed="intval($file->quality_score['shacl_score']) === 10" />
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    
                    <x-slot name="footerActions">
                        <x-filament::button style="--c-400:var(--primary-400);--c-500:var(--primary-500);--c-600:var(--primary-600);" class="!font-medium !ring-1 !ring-primary-600" color="gray" size="lg" outlined x-on:click="$dispatch('close-modal', { id: 'quality-report-modal-{{ $file->id }}' })">
                            {{ __('Close') }}
                        </x-filament::button>
                        @php
                            $score_calculation_guide_file_path = Setting::where('key', 'score_calculation_guide_file_path')->first();
                            $raccommendetions_file_path = Setting::where('key', 'raccommendetions_file_path')->first();
                        @endphp
                        @if ($score_calculation_guide_file_path && $score_calculation_guide_file_path->value)
                            <x-filament::button style="--c-400:var(--primary-400);--c-500:var(--primary-500);--c-600:var(--primary-600);" class="!font-medium !ring-1 !ring-custom-600" color="primary" icon="heroicon-o-document-arrow-down" size="lg" outlined tag="a" target="_blank" :href="route('settings.file.open', base64_encode($score_calculation_guide_file_path->key))">
                                {{ __('Download Score Calculation Guide') }}
                            </x-filament::button>
                        @endif
                        @if ($raccommendetions_file_path && $raccommendetions_file_path->value)
                            <x-filament::button class="!font-medium bg-button-gradient hover:bg-button-gradient-hover text-white ring-1 ring-primary-600" icon="heroicon-o-document-arrow-down" size="lg" target="_blank" tag="a" :href="route('settings.file.open', base64_encode($raccommendetions_file_path->key))">
                                {{ __('Download Raccommendetions') }}
                            </x-filament::button>
                        @endif
                    </x-slot>
                </x-filament::modal>
            @endif
        </div>
    </div>
    <div class="flex justify-center items-center flex-col gap-5">
        <p>
            {{ __('Obtain an automated evaluation for the quality of this file.') }}
        </p>
        @if ($file->quality_assessment_status === GeneralStatus::RUNNING->value)
            <x-filament::loading-indicator class="h-10 w-10 text-primary-600" />
        @else
            {{ ($this->evaluateQualityAction)(['fileId' => $file->id]) }}
        @endif
    </div>
</div>