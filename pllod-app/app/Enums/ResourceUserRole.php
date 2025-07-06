<?php

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum ResourceUserRole: string implements HasDescription, HasLabel
{
    case OWNER = 'OWNER';
    case CONTRIBUTOR = 'CONTRIBUTOR';

    public function getLabel(): string
    {
        return match ($this) {
            self::OWNER => __('Owner'),
            self::CONTRIBUTOR => __('Contributor'),
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::OWNER => __('This user is the owner of the resource.'),
            self::CONTRIBUTOR => __('This user is a contributor to the resource.'),
        };
    }

    public static function values(): array
    {
        return array_map(fn (BackedEnum $case) => $case->value, self::cases());
    }
}
