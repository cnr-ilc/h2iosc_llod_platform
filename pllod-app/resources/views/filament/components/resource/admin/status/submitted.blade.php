@props(['resource', 'label' => null, 'labelSize' => 'sm'])

<div class="flex items-center gap-2">
    <p class="text-{{ $labelSize }} text-gray-900">
        {{ $label ?? __('filament.resources.status.submitted.label') }}: {{ $resource->submission->created_at->diffForHumans() }}
    </p>
</div>
