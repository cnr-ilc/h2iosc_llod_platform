<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Filament\LogoutController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/logout', [LogoutController::class, 'logout'])->name('auth.logout');

Route::get('login/keycloak', [LoginController::class, 'redirectToKeycloak'])->name('login.keycloak');
Route::get('login/keycloak/callback', [LoginController::class, 'handleKeycloakCallback'])->name('login.keycloak.callback');

Route::middleware('auth')->group(function () {
    // Il nome del parametro deve coincidere con quello del controller ($file) per il route model binding
    Route::get('/files/{file}/download', [FileController::class, 'download'])->name('file.download');
    Route::get('/files/{file}/suggestions', [FileController::class, 'suggestions'])->name('file.suggestions');
});

Route::get('/settings/files/{encodedKey}', [SettingsController::class, 'open'])->middleware('auth')->name('settings.file.open');
