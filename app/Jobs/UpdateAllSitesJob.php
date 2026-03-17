<?php

namespace App\Jobs;

use App\Models\Site;
use App\Models\User;
use App\Services\UpdateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateAllSitesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 300;

    public function __construct(
        public User $user,
        public bool $scheduled = false,
    ) {}

    public function handle(UpdateService $updateService): void
    {
        $sites = Site::forUser($this->user)
            ->connected()
            ->get();

        foreach ($sites as $site) {
            try {
                $this->updateSiteFully($site, $updateService);
            } catch (\Throwable $e) {
                Log::error('UpdateAllSites: failed on site', [
                    'site_id' => $site->id,
                    'site_name' => $site->name,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('UpdateAllSites: completed for all sites', [
            'user_id' => $this->user->id,
            'site_count' => $sites->count(),
        ]);
    }

    private function updateSiteFully(Site $site, UpdateService $updateService): void
    {
        // 1. Core update (if available)
        $coreItem = $site->installedItems()
            ->where('type', 'core')
            ->whereNotNull('available_version')
            ->first();

        if ($coreItem) {
            $updateService->createUpdateJob($site, $this->user, [$coreItem->id]);
        }

        // 2. Theme updates
        $themeIds = $site->themes()
            ->whereNotNull('available_version')
            ->pluck('id')
            ->toArray();

        if (! empty($themeIds)) {
            $updateService->createUpdateJob($site, $this->user, $themeIds);
        }

        // 3. Plugin updates
        $pluginIds = $site->plugins()
            ->whereNotNull('available_version')
            ->pluck('id')
            ->toArray();

        if (! empty($pluginIds)) {
            $updateService->createUpdateJob($site, $this->user, $pluginIds);
        }
    }
}
