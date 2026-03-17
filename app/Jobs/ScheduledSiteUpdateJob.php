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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ScheduledSiteUpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 300;

    public function __construct(
        public User $user,
    ) {}

    public function handle(UpdateService $updateService): void
    {
        $sites = Site::forUser($this->user)
            ->connected()
            ->orderBy('name')
            ->get();

        if ($sites->isEmpty()) {
            return;
        }

        // Track which site to update next using cache
        $cacheKey = "auto_update_index_{$this->user->id}";
        $index = Cache::get($cacheKey, 0);

        // Wrap around if we've gone past the end
        if ($index >= $sites->count()) {
            $index = 0;
        }

        $site = $sites[$index];

        // Move to next site for next run
        Cache::put($cacheKey, $index + 1, now()->addDays(7));

        $pendingUpdates = $site->pendingUpdates()->count();

        if ($pendingUpdates === 0) {
            Log::info('ScheduledSiteUpdate: no pending updates', [
                'site' => $site->name,
                'next_index' => $index + 1,
            ]);
            return;
        }

        Log::info('ScheduledSiteUpdate: updating site', [
            'site' => $site->name,
            'pending_updates' => $pendingUpdates,
        ]);

        // Update in order: core → themes → plugins
        $coreItem = $site->installedItems()
            ->where('type', 'core')
            ->whereNotNull('available_version')
            ->first();

        if ($coreItem) {
            $updateService->createUpdateJob($site, $this->user, [$coreItem->id]);
        }

        $themeIds = $site->themes()
            ->whereNotNull('available_version')
            ->pluck('id')
            ->toArray();

        if (! empty($themeIds)) {
            $updateService->createUpdateJob($site, $this->user, $themeIds);
        }

        $pluginIds = $site->plugins()
            ->whereNotNull('available_version')
            ->pluck('id')
            ->toArray();

        if (! empty($pluginIds)) {
            $updateService->createUpdateJob($site, $this->user, $pluginIds);
        }
    }
}
