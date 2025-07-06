@props([
    'passed' => true,
])

<span @class([
    'rounded-full px-[10px] py-[2px] flex items-center font-medium',
    $passed ? 'bg-success-100 text-success-800' : 'bg-danger-100 text-danger-800',
])>
    {{ $passed ? __('Passed') : __('Failed') }}
</span>