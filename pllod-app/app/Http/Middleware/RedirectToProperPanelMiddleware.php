<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Filament\Pages\Dashboard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectToProperPanelMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request):Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->hasRole([UserRole::ADMIN->value, UserRole::SUPER_ADMIN->value])) {
            return redirect()->to(Dashboard::getUrl(panel: 'admin'));
        }

        if (Auth::check() && Auth::user()->hasRole(UserRole::USER->value)) {
            return redirect()->to(Dashboard::getUrl(panel: 'user'));
        }

        return $next($request);
    }
}
