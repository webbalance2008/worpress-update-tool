<?php

namespace App\Models;

use App\Enums\InstalledItemType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstalledItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'type',
        'slug',
        'name',
        'current_version',
        'available_version',
        'is_active',
        'auto_update_enabled',
        'tested_wp_version',
        'last_updated_at',
        'meta',
    ];

    protected $casts = [
        'type' => InstalledItemType::class,
        'is_active' => 'boolean',
        'auto_update_enabled' => 'boolean',
        'last_updated_at' => 'datetime',
        'meta' => 'array',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function hasUpdate(): bool
    {
        return $this->available_version !== null
            && $this->available_version !== $this->current_version;
    }

    public function isMajorUpdate(): bool
    {
        if (! $this->hasUpdate()) {
            return false;
        }

        $currentMajor = (int) explode('.', $this->current_version)[0];
        $availableMajor = (int) explode('.', $this->available_version)[0];

        return $availableMajor > $currentMajor;
    }
}
