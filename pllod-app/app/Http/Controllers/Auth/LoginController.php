<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class LoginController extends Controller
{
    public function redirectToKeycloak()
    {
        return Socialite::driver('keycloak')->redirect();
    }

    public function handleKeycloakCallback()
    {
        $user = Socialite::driver('keycloak')->user();

        if (! $user->email) {
            abort(422, 'User email not found in response. Please contact the administrator.');
        }

        $existingUser = User::firstWhere('email', $user->email);

        if (! $existingUser) {
            $existingUser = new User;
            $existingUser->name = $user?->user['given_name'] ?? null;
            $existingUser->last_name = $user?->user['family_name'] ?? null;
            $existingUser->email = $user->email;
            $existingUser->email_verified_at = now();

            if (empty($existingUser->name)) {
                $existingUser->name = $user->name;
                $existingUser->last_name = null;
            }

            $existingUser->save();
        }

        Auth::login($existingUser);

        $accessTokenResponseBody = $user->accessTokenResponseBody;

        $accessToken = $accessTokenResponseBody['access_token'];
        $refreshToken = $accessTokenResponseBody['refresh_token'];
        $tokenType = $accessTokenResponseBody['token_type'];

        session(['access_token' => $accessToken]);
        session(['refresh_token' => $refreshToken]);
        session(['token_type' => $tokenType]);

        if (Auth::user()->hasRole([UserRole::ADMIN->value, UserRole::SUPER_ADMIN->value])) {
            return redirect()->intended(Dashboard::getUrl(panel: 'admin'));
        }

        if (Auth::user()->hasRole(UserRole::USER->value)) {
            return redirect()->intended(Dashboard::getUrl(panel: 'user'));
        }

        return redirect()->to(Filament::getPanel('auth')->getLoginUrl());
    }
}
