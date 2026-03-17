<?php

namespace App\Services;

use App\Enums\SiteStatus;
use App\Models\AuditLog;
use App\Models\InstalledItem;
use App\Models\Site;
use App\Models\User;
use Illuminate\Support\Collection;

class SiteService
{
    public function __construct(
        private HmacService $hmacService,
    ) {}

    /**
     * Create a new site with a registration token.
     */
    public function createSite(User $user, string $name, string $url): Site
    {
        $site = Site::create([
            'user_id' => $user->id,
            'name' => $name,
            'url' => rtrim($url, '/'),
            'status' => SiteStatus::Pending,
            'registration_token' => $this->hmacService->generateRegistrationToken(),
        ]);

        AuditLog::record('site.created', "Site '{$name}' created", $user->id, $site->id);

        return $site;
    }

    /**
     * Complete registration when an agent connects with a valid token.
     */
    public function completeRegistration(string $token, array $metadata): ?array
    {
        $site = Site::where('registration_token', $token)->first();

        if (! $site) {
            return null;
        }

        $secret = $this->hmacService->generateSecret();

        $site->update([
            'auth_secret' => $secret,
            'registration_token' => null,
            'status' => SiteStatus::Connected,
            'wp_version' => $metadata['wp_version'] ?? null,
            'php_version' => $metadata['php_version'] ?? null,
            'active_theme' => $metadata['active_theme'] ?? null,
            'plugin_version' => $metadata['plugin_version'] ?? null,
            'last_seen_at' => now(),
        ]);

        AuditLog::record('site.registered', "Agent registered for '{$site->name}'", null, $site->id);

        return [
            'site_id' => $site->id,
            'auth_secret' => $secret,
            'dashboard_url' => config('app.url'),
        ];
    }

    /**
     * Process a heartbeat from an agent.
     */
    public function processHeartbeat(Site $site, array $data): void
    {
        $site->update([
            'wp_version' => $data['wp_version'] ?? $site->wp_version,
            'php_version' => $data['php_version'] ?? $site->php_version,
            'active_theme' => $data['active_theme'] ?? $site->active_theme,
            'plugin_version' => $data['plugin_version'] ?? $site->plugin_version,
        ]);

        $site->markSeen();
    }

    /**
     * Sync installed items from agent data.
     */
    public function syncInstalledItems(Site $site, array $items): int
    {
        $syncedSlugs = [];

        foreach ($items as $itemData) {
            InstalledItem::updateOrCreate(
                [
                    'site_id' => $site->id,
                    'type' => $itemData['type'],
                    'slug' => $itemData['slug'],
                ],
                [
                    'name' => $itemData['name'],
                    'current_version' => $itemData['current_version'],
                    'available_version' => $itemData['available_version'] ?? null,
                    'is_active' => $itemData['is_active'] ?? true,
                    'auto_update_enabled' => $itemData['auto_update_enabled'] ?? false,
                    'tested_wp_version' => $itemData['tested_wp_version'] ?? null,
                ]
            );

            $syncedSlugs[] = $itemData['type'] . ':' . $itemData['slug'];
        }

        // Remove items no longer present on the site
        $site->installedItems()
            ->get()
            ->filter(fn (InstalledItem $item) => ! in_array($item->type->value . ':' . $item->slug, $syncedSlugs))
            ->each->delete();

        $site->markSeen();

        return count($items);
    }

    /**
     * Get sites with stale heartbeats and mark them disconnected.
     */
    public function markStaleSites(int $thresholdMinutes = 15): Collection
    {
        $stale = Site::where('status', SiteStatus::Connected)
            ->where('last_seen_at', '<', now()->subMinutes($thresholdMinutes))
            ->get();

        $stale->each(fn (Site $site) => $site->update(['status' => SiteStatus::Disconnected]));

        return $stale;
    }
}
