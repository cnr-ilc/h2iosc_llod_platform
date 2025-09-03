<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Number;

class ResourceFile extends Model
{
    protected $guarded = [
        'id',
    ];

    protected $casts = [
        'health_issues' => 'array',
        'health_warnings' => 'array',
        'quality_warnings' => 'array',
        'quality_score' => 'json',
        'quality_result' => 'json',
        'validation_timestamp' => 'datetime',
        'quality_assessment_timestamp' => 'datetime',
        'can_be_opened_in_vocbench' => 'boolean',
        'is_submittable' => 'boolean',
        'is_synchronising' => 'boolean',
    ];

    /**
     * Get the resource that owns the file.
     */
    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }

    public function getSizeAttribute($value): string
    {
        return Number::fileSize($value, 2);
    }

    // public function getGraphdbUriAttribute($value): ?string
    // {
    //     if ($value) {
    //         return config('graphdb.host.host_url')."/resource?uri={$value}&role=context";
    //     }

    //     return null;
    // }

    /**
     * Get the full path to the file
     */
    public function getPath(): string
    {
        return storage_path("app/private/{$this->path}");
    }
}
