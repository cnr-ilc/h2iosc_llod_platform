<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class SettingsController extends Controller
{
    public function open(string $encodedKey): StreamedResponse
    {
        try {
            $key = base64_decode($encodedKey);
            $setting = Setting::where('key', $key)->first();
            if (! $setting || $setting?->type !== 'file' || Storage::disk('public')->exists(Storage::url($setting->value))) {
                abort(404, 'File not found');
            }

            return Storage::disk('public')->response($setting->value);
        } catch (Throwable $th) {
            abort(404, 'File not found');
        }
    }
}
