<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateSiteRequest;
use App\Jobs\SyncSiteJob;
use App\Models\Site;
use App\Services\AgentApiClient;
use App\Services\SiteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class SiteController extends Controller
{
    public function __construct(
        private SiteService $siteService,
        private AgentApiClient $agentApiClient,
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

    public function pushPluginUpdate(Request $request, Site $site)
    {
        $this->authorize('update', $site);

        $downloadUrl = route('agent-plugin.download');
        $result = $this->agentApiClient->pushPluginUpdate($site, $downloadUrl);

        if ($result && ($result['success'] ?? false)) {
            $version = $result['new_version'] ?? 'latest';
            return back()->with('success', "Agent plugin updated to {$version}.");
        }

        $detail = $result['error'] ?? 'Unknown error — check site connectivity.';
        return back()->with('error', "Failed to push plugin update: {$detail}");
    }

    public function pushPluginUpdateAll(Request $request)
    {
        $sites = Site::where('user_id', $request->user()->id)
            ->where('status', 'connected')
            ->get();

        $downloadUrl = route('agent-plugin.download');
        $success = 0;
        $failed = 0;

        $errors = [];

        foreach ($sites as $site) {
            $result = $this->agentApiClient->pushPluginUpdate($site, $downloadUrl);
            if ($result && ($result['success'] ?? false)) {
                $success++;
            } else {
                $failed++;
                $detail = $result['error'] ?? 'Unknown error';
                $errors[] = "{$site->name}: {$detail}";
            }
        }

        if ($failed && $errors) {
            $errorMsg = implode(' | ', $errors);
            return back()->with('error', "Plugin update: {$success} succeeded, {$failed} failed. {$errorMsg}");
        }

        return back()->with('success', "Plugin update pushed to {$success} site(s).");
    }

    public function downloadAgentPlugin()
    {
        $pluginDir = base_path('wp-agent-plugin');
        $zipPath = storage_path('app/wum-agent-plugin.zip');

        // Build a fresh zip
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Could not create plugin zip.');
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($pluginDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if ($file->isFile()) {
                $relativePath = str_replace($pluginDir . '/', '', $file->getPathname());
                $zip->addFile($file->getPathname(), $relativePath);
            }
        }

        $zip->close();

        return response()->download($zipPath, 'wum-agent-plugin.zip');
    }

    public function destroy(Request $request, Site $site)
    {
        $this->authorize('delete', $site);

        $site->delete();

        return redirect()->route('dashboard')->with('success', 'Site removed.');
    }
}
