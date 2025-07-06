<?php

namespace App\Http\Responses;

use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\LogoutResponse as BaseLogoutResponse;
use Illuminate\Http\RedirectResponse;

class LogoutResponse extends BaseLogoutResponse
{
    public function toResponse($request): RedirectResponse
    {
        return redirect()->to(Filament::getPanel('auth')->getLoginUrl());
    }
}
