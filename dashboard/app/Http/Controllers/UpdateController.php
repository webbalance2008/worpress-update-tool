<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Services\UpdateService;
use Illuminate\Http\Request;

class UpdateController extends Controller
{
    public function __construct(
        private UpdateService $updateService,
    ) {}

    /**
     * Trigger updates for selected items.
     */
    public function triggerUpdate(Request $request, Site $site)
    {
        $this->authorize('update', $site);

        $validated = $request->validate([
            'installed_item_ids' => 'required|array|min:1',
            'installed_item_ids.*' => 'integer|exists:installed_items,id',
        ]);

        $job = $this->updateService->createUpdateJob(
            $site,
            $request->user(),
            $validated['installed_item_ids'],
        );

        return redirect()
            ->route('sites.history', $site)
            ->with('success', "Update job #{$job->id} queued.");
    }

    /**
     * Trigger all available plugin updates.
     */
    public function triggerAllPluginUpdates(Request $request, Site $site)
    {
        $this->authorize('update', $site);

        $job = $this->updateService->createAllPluginUpdatesJob($site, $request->user());

        return redirect()
            ->route('sites.history', $site)
            ->with('success', "Batch update job #{$job->id} queued for all plugins.");
    }

    /**
     * Trigger WordPress core update.
     */
    public function triggerCoreUpdate(Request $request, Site $site)
    {
        $this->authorize('update', $site);

        $job = $this->updateService->createCoreUpdateJob($site, $request->user());

        return redirect()
            ->route('sites.history', $site)
            ->with('success', "Core update job #{$job->id} queued.");
    }

    /**
     * View details of a specific update job.
     */
    public function showJob(Request $request, Site $site, int $jobId)
    {
        $this->authorize('view', $site);

        $job = $site->updateJobs()
            ->with(['items.installedItem', 'riskAssessment', 'healthChecks', 'errorLogs'])
            ->findOrFail($jobId);

        return view('updates.show', compact('site', 'job'));
    }
}
