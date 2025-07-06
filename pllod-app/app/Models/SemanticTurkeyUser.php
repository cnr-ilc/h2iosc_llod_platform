<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SemanticTurkeyUser extends Model
{
    use HasFactory;

    protected $table = 'users_semantic_turkey';

    protected $fillable = [
        'user_id',
        'has_account',
        'is_active',
        'password',
    ];

    protected $casts = [
        'has_account' => 'boolean',
        'is_active' => 'boolean',
        'password' => 'encrypted',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hasAccount(): bool
    {
        return $this->has_account;
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function hasAccountAndIsActive(): bool
    {
        return $this->has_account && $this->is_active;
    }
}
