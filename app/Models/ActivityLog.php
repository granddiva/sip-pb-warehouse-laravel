<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id', 'activity_type', 'description', 'ip_address',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function log(string $activityType, string $description, ?int $userId = null): self
    {
        return self::create([
            'user_id'       => $userId ?? auth()->id(),
            'activity_type' => $activityType,
            'description'   => $description,
            'ip_address'    => request()->ip() ?? '127.0.0.1',
        ]);
    }
}
