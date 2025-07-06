@props(['file'])

@php
    use App\Enums\GeneralStatus;
@endphp

<div class="flex flex-col gap-6 p-6 rounded-xl border border-gray-200">
    <div class="flex gap-3">
        @switch($file->validation_status)
            @case(GeneralStatus::SUCCESS->value && !empty($file->health_warnings))
                <x-heroicon-o-check-circle class="w-7 h-7 text-yellow-700" />
                @break
            @case(GeneralStatus::RUNNING->value)
                <x-heroicon-o-exclamation-circle class="w-7 h-7 text-yellow-700" />
                @break
            @case(GeneralStatus::SUCCESS->value)
                <x-heroicon-o-check-circle class="w-7 h-7 text-green-700" />
                @break
            @case(GeneralStatus::FAILED->value)
                <x-heroicon-o-x-circle class="w-7 h-7 text-red-700" />
                @break
        @endswitch
        <div class="flex flex-col gap-2 w-full">
            @switch($file->validation_status)
                @case(GeneralStatus::SUCCESS->value && !empty($file->health_warnings))
                    <h5 class="text-lg font-semibold">
                        {{ __('The file is partially valid') }}
                    </h5>
                    <p>
                        {{ __('You may evaluate this file’s quality. To open it with VocBench, please address the following issues:') }}
                    </p>
                    <ul class="list-disc list-inside pl-2">
                        @foreach ($file->health_warnings as $warning)
                            <li>{{ $warning }}</li>
                        @endforeach
                    </ul>
                    @break
                @case(GeneralStatus::RUNNING->value)
                    <div class="flex gap-2 justify-between items-start flex-wrap">
                        <h5 class="text-lg font-semibold">
                            {{ __('This file is undergoing validation') }}
                        </h5>
                        <div class="text-sm font-semibold text-warning-800 bg-warning-100 px-2 py-1 rounded-full">
                            {{ __('Validation on-going') }}
                        </div>
                    </div>
                    <p>
                        {{ __('Check back in a few moments.') }}
                    </p>
                    @break
                @case(GeneralStatus::SUCCESS->value)
                    <h5 class="text-lg font-semibold">
                        {{ __('The file is valid') }}
                    </h5>
                    <p>
                        {{ __('You may open this file with VocBench and evaluate its quality.') }}
                    </p>
                    @break
                @case(GeneralStatus::FAILED->value)
                    <h5 class="text-lg font-semibold">
                        {{ __('The file is invalid') }}
                    </h5>
                    @if (!empty($file->health_issues))
                        <h6 class="text-base">
                            {{ __('Detected errors:') }}
                        </h6>
                        <ul class="list-disc list-inside pl-2">
                            @foreach ($file->health_issues as $issue)
                                <li>{{ $issue }}</li>
                            @endforeach
                        </ul>
                    @endif
                    @break
            @endswitch
        </div>
    </div>
</div>
