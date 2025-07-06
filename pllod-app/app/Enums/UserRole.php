<?php

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasColor, HasDescription, HasLabel
{
    case ADMIN = 'ADMIN';
    case SUPER_ADMIN = 'SUPER_ADMIN';
    case USER = 'USER';

    public function getLabel(): string
    {
        return match ($this) {
            self::ADMIN => __('Admin'),
            self::SUPER_ADMIN => __('Super Admin'),
            self::USER => __('User'),
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::ADMIN => __('Admin'),
            self::SUPER_ADMIN => __('Super Admin'),
            self::USER => __('User'),
        };
    }

    public function getColor(): array|string|null
    {
        return match ($this) {
            self::ADMIN => 'success',
            self::SUPER_ADMIN => 'danger',
            self::USER => 'warning',
        };
    }

    public static function values(): array
    {
        return array_map(fn (BackedEnum $case) => $case->value, self::cases());
    }
}
