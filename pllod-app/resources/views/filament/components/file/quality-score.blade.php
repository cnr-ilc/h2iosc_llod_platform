@props([
    'score' => null,
    'maxScore' => 10,
    'successScoreShift' => [8, 9],
    'maxScoreLabel' => __('Passed'),
    'minScore' => 0,
    'failedScoreShift' => [1, 2, 3, 4, 5],
    'minScoreLabel' => __('Failed'),
    'scoreLabel' => __('out of'),
])

@php
    $statusClasses = '';
    $displayLabel = '';

    // Determine status classes (color)
    if ($score === $maxScore || ($score !== null && !empty($successScoreShift) && in_array($score, $successScoreShift, true))) {
        $statusClasses = 'bg-success-100 text-success-800';
    } elseif ($score === $minScore || ($score !== null && !empty($failedScoreShift) && in_array($score, $failedScoreShift, true))) {
        $statusClasses = 'bg-danger-100 text-danger-800';
    } else {
        // Default to warning for scores not in success/danger categories, or if score is null
        $statusClasses = 'bg-warning-100 text-warning-800';
    }

    $displayLabel = "{$score} {$scoreLabel} {$maxScore}";
@endphp

<span @class([
    'rounded-full px-[10px] py-[2px] flex items-center font-medium',
    $statusClasses,
])>
    {{ $displayLabel }}
</span>