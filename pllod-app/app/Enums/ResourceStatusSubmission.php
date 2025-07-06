<?php

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum ResourceStatusSubmission: string implements HasDescription, HasLabel
{
    case NOT_SUBMITTED = 'NOT_SUBMITTED';
    case PENDING = 'PENDING';
    case REJECTED = 'REJECTED';
    case APPROVED = 'APPROVED';

    public function getLabel(): string
    {
        return match ($this) {
            self::NOT_SUBMITTED => __('Not submitted'),
            self::PENDING => __('Pending approval'),
            self::REJECTED => __('Rejected'),
            self::APPROVED => __('Approved'),
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::NOT_SUBMITTED => __('The resource is not submitted'),
            self::PENDING => __('The resource is pending approval by the administrator'),
            self::REJECTED => __('The resource is rejected by the administrator'),
            self::APPROVED => __('The resource is approved by the administrator'),
        };
    }

    public static function values(): array
    {
        return array_map(fn (BackedEnum $case) => $case->value, self::cases());
    }
}
