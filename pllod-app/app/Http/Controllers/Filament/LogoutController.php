<?php

namespace App\Http\Controllers\Filament;

use Filament\Facades\Filament;
use Filament\Http\Controllers\Auth\LogoutController as FilamentLogoutController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class LogoutController extends FilamentLogoutController
{
    public function logout(Request $request): RedirectResponse
    {
        $refreshToken = session('refresh_token');
        $accessToken = session('access_token');
        $tokenType = session('token_type');

        if ($refreshToken && $accessToken) {
            $response = $this->revokeRefreshToken($refreshToken, $accessToken, $tokenType);
            if ($response) {
                session()->forget('refresh_token');
                session()->forget('access_token');
                session()->forget('token_type');
            }
        }

        Filament::auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->intended(Filament::getPanel('auth')->getLoginUrl());
    }

    private function revokeRefreshToken(string $refreshToken, string $accessToken, string $tokenType): bool
    {
        $wellKnownUrl = config('services.keycloak.well_known_url');
        $openidConfiguration = Http::get($wellKnownUrl)->json();
        $logoutUrl = $openidConfiguration['end_session_endpoint'];

        $response = Http::withToken($accessToken, $tokenType)->asForm()->post($logoutUrl, [
            'client_id' => config('services.keycloak.client_id'),
            'client_secret' => config('services.keycloak.client_secret'),
            'refresh_token' => $refreshToken,
        ]);

        return $response->successful();
    }
}
