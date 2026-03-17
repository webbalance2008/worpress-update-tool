<?php

namespace App\Models;

use App\Enums\RiskLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskAssessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'update_job_id',
        'site_id',
        'score',
        'level',
        'explanation',
        'factors',
    ];

    protected $casts = [
        'level' => RiskLevel::class,
        'score' => 'integer',
        'factors' => 'array',
    ];

    public function updateJob(): BelongsTo
    {
        return $this->belongsTo(UpdateJob::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function isHigh(): bool
    {
        return $this->level === RiskLevel::High;
    }
}
