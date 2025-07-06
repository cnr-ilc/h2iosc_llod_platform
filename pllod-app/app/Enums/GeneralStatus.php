<?php

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum GeneralStatus: string implements HasDescription, HasLabel
{
    case RUNNING = 'RUNNING';
    case FAILED = 'FAILED';
    case SUCCESS = 'SUCCESS';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::RUNNING => __('Running'),
            self::FAILED => __('Failed'),
            self::SUCCESS => __('Success'),
        };
    }

    public function getDescription(): ?string
    {
        return match ($this) {
            self::RUNNING => __('The process is currently running.'),
            self::FAILED => __('The process failed.'),
            self::SUCCESS => __('The process completed successfully.'),
        };
    }

    public static function values(): array
    {
        return array_map(fn (BackedEnum $case) => $case->value, self::cases());
    }
}
