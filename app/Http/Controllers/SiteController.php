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
        $siteResults = [];

        foreach ($sites as $site) {
            $result = $this->agentApiClient->pushPluginUpdate($site, $downloadUrl);
            $siteResults[] = [
                'name'        => $site->name,
                'url'         => $site->url,
                'success'     => (bool) ($result['success'] ?? false),
                'new_version' => $result['new_version'] ?? null,
                'error'       => ($result['success'] ?? false) ? null : ($result['error'] ?? 'Unknown error'),
            ];
        }

        if ($request->wantsJson()) {
            // Read plugin version and changelog
            $version = self::getPluginVersion();
            $changelog = self::getLatestChangelog();

            return response()->json([
                'sites'      => $siteResults,
                'version'    => $version,
                'changelog'  => $changelog,
            ]);
        }

        $success = count(array_filter($siteResults, fn($r) => $r['success']));
        $failed = count(array_filter($siteResults, fn($r) => ! $r['success']));

        if ($failed) {
            $errors = array_map(fn($r) => "{$r['name']}: {$r['error']}", array_filter($siteResults, fn($r) => ! $r['success']));
            return back()->with('error', "Plugin update: {$success} succeeded, {$failed} failed. " . implode(' | ', $errors));
        }

        return back()->with('success', "Plugin update pushed to {$success} site(s).");
    }

    /**
     * Read the agent plugin version from the plugin header.
     */
    private static function getPluginVersion(): string
    {
        $path = base_path('wp-agent-plugin/wum-agent.php');
        $content = file_get_contents($path);

        if (preg_match('/^\s*\*\s*Version:\s*(.+)$/m', $content, $matches)) {
            return trim($matches[1]);
        }

        return 'unknown';
    }

    /**
     * Extract the latest version's changelog entries from CHANGELOG.md.
     */
    private static function getLatestChangelog(): ?string
    {
        $path = base_path('wp-agent-plugin/CHANGELOG.md');

        if (! file_exists($path)) {
            return null;
        }

        $content = file_get_contents($path);

        // Extract the first version section (everything between the first ## and the second ##)
        if (preg_match('/^## .+?\n(.*?)(?=\n## |\z)/ms', $content, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    public function agentPluginChangelog()
    {
        $version = self::getPluginVersion();
        $changelogPath = base_path('wp-agent-plugin/CHANGELOG.md');
        $changelog = file_exists($changelogPath) ? file_get_contents($changelogPath) : null;

        // Parse changelog into structured data
        $versions = [];
        if ($changelog) {
            // Split by ## version headers
            preg_match_all('/^## (.+?)$(.*?)(?=^## |\z)/ms', $changelog, $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                $ver = trim($match[1]);
                $entries = array_filter(
                    array_map('trim', explode("\n", trim($match[2]))),
                    fn($line) => str_starts_with($line, '- ')
                );
                $entries = array_map(fn($line) => ltrim($line, '- '), $entries);
                $versions[] = ['version' => $ver, 'entries' => $entries];
            }
        }

        return view('agent-plugin.changelog', compact('version', 'versions'));
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
                // Include parent directory name so zip extracts as wp-agent-plugin/
                $zip->addFile($file->getPathname(), 'wp-agent-plugin/' . $relativePath);
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
