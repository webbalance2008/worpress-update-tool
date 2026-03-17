<?php

namespace App\Models;

use App\Enums\SiteStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Site extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'url',
        'auth_secret',
        'registration_token',
        'status',
        'wp_version',
        'php_version',
        'active_theme',
        'plugin_version',
        'last_seen_at',
        'meta',
    ];

    protected $casts = [
        'status' => SiteStatus::class,
        'auth_secret' => 'encrypted',
        'last_seen_at' => 'datetime',
        'meta' => 'array',
    ];

    protected $hidden = [
        'auth_secret',
        'registration_token',
    ];

    // ── Relationships ──

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function installedItems(): HasMany
    {
        return $this->hasMany(InstalledItem::class);
    }

    public function updateJobs(): HasMany
    {
        return $this->hasMany(UpdateJob::class);
    }

    public function healthChecks(): HasMany
    {
        return $this->hasMany(HealthCheck::class);
    }

    public function errorLogs(): HasMany
    {
        return $this->hasMany(ErrorLog::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    // ── Scopes ──

    public function scopeConnected($query)
    {
        return $query->where('status', SiteStatus::Connected);
    }

    public function scopeForUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }

    // ── Helpers ──

    public function isConnected(): bool
    {
        return $this->status === SiteStatus::Connected;
    }

    public function plugins(): HasMany
    {
        return $this->installedItems()->where('type', 'plugin');
    }

    public function themes(): HasMany
    {
        return $this->installedItems()->where('type', 'theme');
    }

    public function pendingUpdates()
    {
        return $this->installedItems()->whereNotNull('available_version');
    }

    public function markSeen(): void
    {
        $this->update(['last_seen_at' => now(), 'status' => SiteStatus::Connected]);
    }
}
