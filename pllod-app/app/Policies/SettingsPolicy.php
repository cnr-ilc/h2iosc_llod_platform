<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Settings;
use App\Models\User;

class SettingsPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::ADMIN->value);
    }

    public function view(User $user, Settings $settings): bool
    {
        return $user->hasRole(UserRole::ADMIN->value);
    }

    public function update(User $user, Settings $settings): bool
    {
        return $user->hasRole(UserRole::ADMIN->value);
    }

    public function delete(User $user, Settings $settings): bool
    {
        return $user->hasRole(UserRole::ADMIN->value);
    }

    public function restore(User $user, Settings $settings): bool
    {
        return $user->hasRole(UserRole::ADMIN->value);
    }

    public function forceDelete(User $user, Settings $settings): bool
    {
        return $user->hasRole(UserRole::ADMIN->value);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::ADMIN->value);
    }
}
