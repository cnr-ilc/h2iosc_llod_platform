<?php

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum ResourceStatusImport: string implements HasDescription, HasLabel
{
    case NOT_IMPORTED = 'NOT_IMPORTED';
    case PENDING = 'PENDING';
    case IMPORTED = 'IMPORTED';
    case FAILED = 'FAILED';

    public function getLabel(): string
    {
        return match ($this) {
            self::NOT_IMPORTED => __('Not imported'),
            self::PENDING => __('Pending import'),
            self::IMPORTED => __('Imported'),
            self::FAILED => __('Import failed'),
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::NOT_IMPORTED => __('The resource is not imported'),
            self::PENDING => __('The resource is pending import'),
            self::FAILED => __('The resource import failed'),
            self::IMPORTED => __('The resource is imported'),
        };
    }

    public static function values(): array
    {
        return array_map(fn (BackedEnum $case) => $case->value, self::cases());
    }
}
