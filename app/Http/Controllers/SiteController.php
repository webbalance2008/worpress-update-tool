<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateSiteRequest;
use App\Jobs\SyncSiteJob;
use App\Models\Site;
use App\Services\SiteService;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    public function __construct(
        private SiteService $siteService,
    ) {}

    public function show(Request $request, Site $site)
    {
        $this->authorize('view', $site);

        $site->load([
            'installedItems' => fn ($q) => $q->orderBy('type')->orderBy('name'),
        ]);

        $pendingUpdates = $site->installedItems->filter->hasUpdate();

        $recentJobs = $site->updateJobs()
            ->with('items')
            ->latest()
            ->take(10)
            ->get();

        $latestHealthCheck = $site->healthChecks()->latest()->first();

        $recentErrors = $site->errorLogs()
            ->latest()
            ->take(10)
            ->get();

        return view('sites.show', compact(
            'site', 'pendingUpdates', 'recentJobs', 'latestHealthCheck', 'recentErrors'
        ));
    }

    public function create()
    {
        return view('sites.create');
    }

    public function store(CreateSiteRequest $request)
    {
        $site = $this->siteService->createSite(
            $request->user(),
            $request->validated('name'),
            $request->validated('url'),
        );

        return redirect()
            ->route('sites.show', $site)
            ->with('success', 'Site created. Use the registration token to connect the agent plugin.');
    }

    public function updates(Request $request, Site $site)
    {
        $this->authorize('view', $site);

        $site->load('installedItems');
        $pendingUpdates = $site->installedItems->filter->hasUpdate();

        return view('sites.updates', compact('site', 'pendingUpdates'));
    }

    public function history(Request $request, Site $site)
    {
        $this->authorize('view', $site);

        $jobs = $site->updateJobs()
            ->with(['items', 'riskAssessment'])
            ->latest()
            ->paginate(20);

        return view('sites.history', compact('site', 'jobs'));
    }

    public function healthChecks(Request $request, Site $site)
    {
        $this->authorize('view', $site);

        $checks = $site->healthChecks()
            ->latest()
            ->paginate(20);

        return view('sites.health-checks', compact('site', 'checks'));
    }

    public function errors(Request $request, Site $site)
    {
        $this->authorize('view', $site);

        $errors = $site->errorLogs()
            ->latest()
            ->paginate(20);

        return view('sites.errors', compact('site', 'errors'));
    }

    public function sync(Request $request, Site $site)
    {
        $this->authorize('update', $site);

        SyncSiteJob::dispatch($site)->onQueue('sync');

        return back()->with('success', 'Sync job queued.');
    }

    public function destroy(Request $request, Site $site)
    {
        $this->authorize('delete', $site);

        $site->delete();

        return redirect()->route('dashboard')->with('success', 'Site removed.');
    }
}
