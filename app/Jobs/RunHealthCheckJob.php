<?php

namespace App\Jobs;

use App\Models\Site;
use App\Models\UpdateJob;
use App\Services\HealthCheckService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RunHealthCheckJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 60;

    public function __construct(
        public Site $site,
        public ?UpdateJob $updateJob = null,
    ) {
        $this->delay = 10;
    }

    public function handle(HealthCheckService $healthCheckService): void
    {
        Log::info('Running health check', [
            'site_id' => $this->site->id,
            'update_job_id' => $this->updateJob?->id,
        ]);

        $healthCheckService->runChecks($this->site, $this->updateJob);
    }
}
