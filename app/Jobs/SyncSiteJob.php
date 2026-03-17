<?php

namespace App\Jobs;

use App\Models\Site;
use App\Services\AgentApiClient;
use App\Services\SiteService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncSiteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 30;

    public function __construct(
        public Site $site,
    ) {}

    public function handle(AgentApiClient $apiClient, SiteService $siteService): void
    {
        $response = $apiClient->sendToAgent($this->site, 'GET', 'installed-items');

        if (! $response || ! ($response['success'] ?? false)) {
            Log::warning('Site sync failed', ['site_id' => $this->site->id]);
            return;
        }

        $siteService->syncInstalledItems($this->site, $response['items'] ?? []);

        Log::info('Site sync completed', ['site_id' => $this->site->id]);
    }
}
