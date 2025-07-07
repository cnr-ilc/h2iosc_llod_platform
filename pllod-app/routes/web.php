<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Filament\LogoutController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\SettingsController;
use App\Models\ResourceFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

Route::post('/auth/logout', [LogoutController::class, 'logout']);

Route::get('login/keycloak', [LoginController::class, 'redirectToKeycloak'])->name('login.keycloak');
Route::get('login/keycloak/callback', [LoginController::class, 'handleKeycloakCallback']);

Route::get('/files/{id}/download', [FileController::class, 'download'])->middleware('auth')->name('file.download');

Route::get('/files/{id}/suggestions', function ($id) {
    $file = ResourceFile::find($id);

    $quality_warnings_array = $file->quality_warnings;

    $file_name = Str::slug($file->name);

    $content = '';
    $content .= '*** SUGGESTIONS ***'."\n\n\n";
    if (is_array($quality_warnings_array)) {
        $content .= implode("\n\n", $quality_warnings_array);
    } else {
        $content .= $quality_warnings_array ?? '';
    }

    return response()->streamDownload(function () use ($content) {
        echo $content;
    }, $file_name.'_suggestions.txt', [
        'Content-Type' => 'text/plain',
    ]);
})->name('file.suggestions');

Route::get('/settings/files/{encodedKey}', [SettingsController::class, 'open'])->middleware('auth')->name('settings.file.open');
