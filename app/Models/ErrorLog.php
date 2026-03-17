<?php

namespace App\Models;

use App\Enums\ErrorSeverity;
use App\Enums\ErrorSource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErrorLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'update_job_id',
        'source',
        'severity',
        'message',
        'context',
        'resolved_at',
    ];

    protected $casts = [
        'source' => ErrorSource::class,
        'severity' => ErrorSeverity::class,
        'context' => 'array',
        'resolved_at' => 'datetime',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function updateJob(): BelongsTo
    {
        return $this->belongsTo(UpdateJob::class);
    }

    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }

    public function resolve(): void
    {
        $this->update(['resolved_at' => now()]);
    }
}
