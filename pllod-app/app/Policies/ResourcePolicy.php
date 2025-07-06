<?php

namespace App\Policies;

use App\Enums\ResourceStatusImport;
use App\Enums\UserRole;
use App\Models\Resource;
use App\Models\User;

class ResourcePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Resource $resource): bool
    {
        return ($user->hasRole(UserRole::ADMIN->value) || $resource->users()->where('user_id', $user->id)->exists()) && $resource->status_import === ResourceStatusImport::IMPORTED->value;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole([UserRole::ADMIN->value, UserRole::USER->value]);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Resource $resource): bool
    {
        return $user->hasRole(UserRole::ADMIN->value);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Resource $resource): bool
    {
        return $user->hasRole([UserRole::ADMIN->value]) || $resource->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Resource $resource): bool
    {
        return $user->hasRole(UserRole::ADMIN->value);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Resource $resource): bool
    {
        return $user->hasRole(UserRole::ADMIN->value);
    }
}
