<?php

namespace App\Observers;

use App\Enums\UserRole;
use App\Jobs\ActivateSemanticTurkeyUser;
use App\Jobs\RegisterSemanticTurkeyUser;
use App\Models\User;
use Illuminate\Support\Facades\Bus;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        $user->assignRole([UserRole::USER->value]);
        if ($user->hasRole(UserRole::USER->value)) {
            $chain = Bus::chain([
                new RegisterSemanticTurkeyUser($user),
                new ActivateSemanticTurkeyUser($user),
            ]);
            $chain->dispatch();
        }
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        //
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        //
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}
