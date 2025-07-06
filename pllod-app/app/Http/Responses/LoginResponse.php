<?php

namespace App\Http\Responses;

use App\Enums\UserRole;
use Filament\Http\Responses\Auth\LoginResponse as BaseLogin;
use Filament\Pages\Dashboard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Livewire\Features\SupportRedirects\Redirector;

class LoginResponse extends BaseLogin
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        if (Auth::user()->hasRole([UserRole::ADMIN->value, UserRole::SUPER_ADMIN->value])) {
            return redirect()->to(Dashboard::getUrl(panel: 'admin'));
        }

        if (Auth::user()->hasRole(UserRole::USER->value)) {
            return redirect()->to(Dashboard::getUrl(panel: 'user'));
        }

        return parent::toResponse($request);
    }
}
