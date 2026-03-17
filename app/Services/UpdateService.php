<?php

namespace App\Services;

use App\Enums\UpdateJobStatus;
use App\Jobs\ExecuteUpdateJob;
use App\Models\AuditLog;
use App\Models\InstalledItem;
use App\Models\Site;
use App\Models\UpdateJob;
use App\Models\UpdateJobItem;
use App\Models\User;

class UpdateService
{
    public function __construct(
        private RiskAssessmentService $riskService,
    ) {}

    /**
     * Create an update job for specific items and dispatch it.
     *
     * @param  array<int>  $installedItemIds  IDs of InstalledItem records to update
     */
    public function createUpdateJob(Site $site, User $user, array $installedItemIds): UpdateJob
    {
        $items = InstalledItem::whereIn('id', $installedItemIds)
            ->where('site_id', $site->id)
            ->whereNotNull('available_version')
            ->get();

        $type = $items->pluck('type')->unique()->count() > 1 ? 'batch' : ($items->first()?->type->value ?? 'batch');

        $job = UpdateJob::create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'type' => $type,
            'status' => UpdateJobStatus::Pending,
        ]);

        foreach ($items as $item) {
            UpdateJobItem::create([
                'update_job_id' => $job->id,
                'installed_item_id' => $item->id,
                'type' => $item->type->value,
                'slug' => $item->slug,
                'old_version' => $item->current_version,
                'requested_version' => $item->available_version,
                'status' => 'pending',
            ]);
        }

        // Run risk assessment before dispatching
        $this->riskService->assess($job);

        AuditLog::record(
            'update.triggered',
            "Update job #{$job->id} created for {$items->count()} items on '{$site->name}'",
            $user->id,
            $site->id,
        );

        ExecuteUpdateJob::dispatch($job)->onQueue('updates');

        return $job;
    }

    /**
     * Create an update job for WordPress core.
     */
    public function createCoreUpdateJob(Site $site, User $user): UpdateJob
    {
        $coreItem = $site->installedItems()
            ->where('type', 'core')
            ->whereNotNull('available_version')
            ->firstOrFail();

        return $this->createUpdateJob($site, $user, [$coreItem->id]);
    }

    /**
     * Create an update job for all available plugin updates on a site.
     */
    public function createAllPluginUpdatesJob(Site $site, User $user): UpdateJob
    {
        $pluginIds = $site->plugins()
            ->whereNotNull('available_version')
            ->pluck('id')
            ->toArray();

        return $this->createUpdateJob($site, $user, $pluginIds);
    }

    /**
     * Process results reported back from the agent.
     */
    public function processUpdateResult(UpdateJob $job, array $itemResults): void
    {
        foreach ($itemResults as $result) {
            $jobItem = UpdateJobItem::find($result['update_job_item_id']);

            if (! $jobItem || $jobItem->update_job_id !== $job->id) {
                continue;
            }

            $jobItem->update([
                'resulting_version' => $result['resulting_version'] ?? null,
                'status' => $result['status'],
                'raw_result' => $result['raw_result'] ?? null,
                'error_message' => $result['error_message'] ?? null,
                'completed_at' => now(),
            ]);

            // Update the installed item's current version if successful
            if ($result['status'] === 'completed' && $jobItem->installedItem) {
                $jobItem->installedItem->update([
                    'current_version' => $result['resulting_version'],
                    'available_version' => null,
                ]);
            }
        }

        $job->markCompleted();
    }
}
