<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'activity_type',
        'description',
        'subject_type',
        'subject_id',
        'properties',
        'ip_address',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Log an activity with optional polymorphic subject and old/new audit properties.
     */
    public static function log(
        string $activityType,
        string $description,
        $subjectOrUserId = null,
        ?array $properties = null,
        ?int $userId = null
    ): self {
        $subject = null;
        $resolvedUserId = $userId;

        if ($subjectOrUserId instanceof Model) {
            $subject = $subjectOrUserId;
        } elseif (is_numeric($subjectOrUserId)) {
            $resolvedUserId = (int) $subjectOrUserId;
        }

        return self::create([
            'user_id'       => $resolvedUserId ?? auth()->id(),
            'activity_type' => $activityType,
            'description'   => $description,
            'subject_type'  => $subject ? get_class($subject) : null,
            'subject_id'    => $subject?->getKey(),
            'properties'    => $properties,
            'ip_address'    => request()->ip() ?? '127.0.0.1',
        ]);
    }
}
