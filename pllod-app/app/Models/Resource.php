<?php

namespace App\Models;

use App\Enums\GeneralStatus;
use App\Enums\ResourceUserRole;
use App\Observers\ResourceObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use OwenIt\Auditing\Contracts\Auditable;

#[ObservedBy(ResourceObserver::class)]
class Resource extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $guarded = [
        'id',
    ];

    protected $auditInclude = [
        'status_import',
    ];

    public function files(): HasMany
    {
        return $this->hasMany(ResourceFile::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withTimestamps();
    }

    public function submission(): HasOne
    {
        return $this->hasOne(Submission::class);
    }

    public function getOwnerAttribute(): User
    {
        return $this->users()->where('role', ResourceUserRole::OWNER)->first();
    }

    public function getCanBeSubmittedAttribute(): bool
    {
        // TODO: Check if the resource can be submitted
        foreach ($this->files as $file) {
            if ($file->validation_status !== GeneralStatus::SUCCESS->value) {
                return false;
            }
        }

        return true;
    }
}
