<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'site_id',
        'action',
        'description',
        'ip_address',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public static function record(
        string $action,
        ?string $description = null,
        ?int $userId = null,
        ?int $siteId = null,
        ?array $meta = null,
    ): self {
        return self::create([
            'user_id' => $userId ?? auth()->id(),
            'site_id' => $siteId,
            'action' => $action,
            'description' => $description,
            'ip_address' => request()->ip(),
            'meta' => $meta,
        ]);
    }
}
