<?php

namespace App\Models;

use App\Enums\UpdateJobStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UpdateJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'user_id',
        'type',
        'status',
        'started_at',
        'completed_at',
        'summary',
    ];

    protected $casts = [
        'status' => UpdateJobStatus::class,
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(UpdateJobItem::class);
    }

    public function riskAssessment(): HasOne
    {
        return $this->hasOne(RiskAssessment::class);
    }

    public function healthChecks(): HasMany
    {
        return $this->hasMany(HealthCheck::class);
    }

    public function errorLogs(): HasMany
    {
        return $this->hasMany(ErrorLog::class);
    }

    public function markInProgress(): void
    {
        $this->update([
            'status' => UpdateJobStatus::InProgress,
            'started_at' => now(),
        ]);
    }

    public function markCompleted(string $summary = null): void
    {
        $failedCount = $this->items()->where('status', 'failed')->count();
        $totalCount = $this->items()->count();

        $status = match (true) {
            $failedCount === 0 => UpdateJobStatus::Completed,
            $failedCount === $totalCount => UpdateJobStatus::Failed,
            default => UpdateJobStatus::PartiallyFailed,
        };

        $this->update([
            'status' => $status,
            'completed_at' => now(),
            'summary' => $summary,
        ]);
    }
}
