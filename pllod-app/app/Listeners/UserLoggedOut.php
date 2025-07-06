<?php

namespace App\Listeners;

use App\Enums\UserRole;
use App\Jobs\ResetPasswordSemanticTurkeyUser;

class UserLoggedOut
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        $user = $event->user;
        $userIsUser = $user->hasRole(UserRole::USER->value);
        $semanticTurkeyUser = $user->semanticTurkeyUser;
        $userHasSemanticTurkey = $semanticTurkeyUser && $semanticTurkeyUser->hasAccountAndIsActive();

        if ($userIsUser && $userHasSemanticTurkey) {
            ResetPasswordSemanticTurkeyUser::dispatch($user);
        }
    }
}
