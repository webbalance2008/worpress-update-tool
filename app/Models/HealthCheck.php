<?php

namespace App\Models;

use App\Enums\HealthCheckStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthCheck extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'update_job_id',
        'status',
        'checks',
        'summary',
    ];

    protected $casts = [
        'status' => HealthCheckStatus::class,
        'checks' => 'array',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function updateJob(): BelongsTo
    {
        return $this->belongsTo(UpdateJob::class);
    }

    public function passedAll(): bool
    {
        return $this->status === HealthCheckStatus::Passed;
    }

    public function getCheckResult(string $key): ?array
    {
        return $this->checks[$key] ?? null;
    }
}
