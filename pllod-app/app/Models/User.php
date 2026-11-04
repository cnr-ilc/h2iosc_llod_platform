<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Observers\UserObserver;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[ObservedBy([UserObserver::class])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are not mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [
        'id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'auth') {
            return true;
        }

        if ($panel->getId() === 'admin' && $this->hasRole([UserRole::ADMIN->value, UserRole::SUPER_ADMIN->value])) {
            return true;
        }

        if ($panel->getId() === 'user' && $this->hasRole(UserRole::USER->value)) {
            return true;
        }

        return false;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function resources(): BelongsToMany
    {
        return $this->belongsToMany(Resource::class);
    }

    public function semanticTurkeyUser(): HasOne
    {
        return $this->hasOne(SemanticTurkeyUser::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class)->orderBy('created_at', 'desc');
    }

    /**
     * Check if the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(UserRole::ADMIN->value);
    }

    /**
     * Check if the user is a super admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole(UserRole::SUPER_ADMIN->value);
    }

    /**
     * Check if the user is an admin or super admin.
     */
    public function isAdminOrSuperAdmin(): bool
    {
        return $this->isAdmin() || $this->isSuperAdmin();
    }

    /**
     * Check if the user is a user.
     */
    public function isUser(): bool
    {
        return $this->hasRole(UserRole::USER->value);
    }
}
