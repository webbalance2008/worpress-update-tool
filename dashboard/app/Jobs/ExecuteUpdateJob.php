<?php

namespace App\Jobs;

use App\Enums\UpdateJobStatus;
use App\Models\UpdateJob;
use App\Services\AgentApiClient;
use App\Services\ErrorReportingService;
use App\Services\UpdateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExecuteUpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 120;

    public function __construct(
        public UpdateJob $updateJob,
    ) {}

    public function handle(AgentApiClient $apiClient, UpdateService $updateService, ErrorReportingService $errorService): void
    {
        $job = $this->updateJob;
        $site = $job->site;

        $job->markInProgress();

        // Build the items payload for the agent
        $itemsPayload = $job->items->map(fn ($item) => [
            'update_job_item_id' => $item->id,
            'type' => $item->type,
            'slug' => $item->slug,
            'version' => $item->requested_version,
        ])->toArray();

        // Send update request to the agent
        $response = $apiClient->executeUpdate($site, $job->id, $itemsPayload);

        if (! $response || ! ($response['success'] ?? false)) {
            $errorMessage = $response['error']['message'] ?? 'Agent did not respond or returned an error.';

            $job->update([
                'status' => UpdateJobStatus::Failed,
                'completed_at' => now(),
                'summary' => "Failed: {$errorMessage}",
            ]);

            $errorService->logUpdateError($site, $job->id, $errorMessage, [
                'response' => $response,
            ]);

            Log::error('Update execution failed', ['job_id' => $job->id, 'site_id' => $site->id]);

            return;
        }

        // Process the results returned by the agent
        $updateService->processUpdateResult($job, $response['results'] ?? []);

        // Dispatch health check after update completes
        RunHealthCheckJob::dispatch($site, $job)->onQueue('health-checks');

        Log::info('Update execution completed', ['job_id' => $job->id, 'status' => $job->fresh()->status]);
    }

    public function failed(\Throwable $exception): void
    {
        $this->updateJob->update([
            'status' => UpdateJobStatus::Failed,
            'completed_at' => now(),
            'summary' => "Job failed with exception: {$exception->getMessage()}",
        ]);

        Log::error('ExecuteUpdateJob failed', [
            'job_id' => $this->updateJob->id,
            'exception' => $exception->getMessage(),
        ]);
    }
}
