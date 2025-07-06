<?php

namespace App\Observers;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

class SettingObserver
{
    public function deleted(Setting $setting): void
    {
        if ($setting->type === 'file' && ! empty($setting->value)) {
            Storage::disk('public')->delete($setting->value);
        }
    }

    public function updating(Setting $setting): void
    {
        if ($setting->type === 'file' && $setting->isDirty('value') && ! empty($setting->getOriginal('value'))) {
            Storage::disk('public')->delete($setting->getOriginal('value'));
        }
    }
}
