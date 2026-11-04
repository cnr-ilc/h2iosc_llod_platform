<?php

namespace App\Http\Responses;

use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;

class LogoutResponse extends \Filament\Auth\Http\Responses\LogoutResponse
{
    public function toResponse($request): RedirectResponse
    {
        return redirect()->to(Filament::getPanel('auth')->getLoginUrl());
    }
}
