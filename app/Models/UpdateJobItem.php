<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UpdateJobItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'update_job_id',
        'installed_item_id',
        'type',
        'slug',
        'old_version',
        'requested_version',
        'resulting_version',
        'status',
        'raw_result',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'raw_result' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function updateJob(): BelongsTo
    {
        return $this->belongsTo(UpdateJob::class);
    }

    public function installedItem(): BelongsTo
    {
        return $this->belongsTo(InstalledItem::class);
    }

    public function succeeded(): bool
    {
        return $this->status === 'completed'
            && $this->resulting_version === $this->requested_version;
    }
}
