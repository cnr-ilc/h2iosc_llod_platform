<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Filament\LogoutController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/logout', [LogoutController::class, 'logout']);

Route::get('login/keycloak', [LoginController::class, 'redirectToKeycloak'])->name('login.keycloak');
Route::get('login/keycloak/callback', [LoginController::class, 'handleKeycloakCallback']);

Route::get('/files/{id}/download', [FileController::class, 'download'])->middleware('auth')->name('file.download');

Route::get('/settings/files/{encodedKey}', [SettingsController::class, 'open'])->middleware('auth')->name('settings.file.open');
