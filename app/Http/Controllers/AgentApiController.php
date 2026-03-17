<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Models\UpdateJob;
use App\Services\ErrorReportingService;
use App\Services\SiteService;
use App\Services\UpdateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentApiController extends Controller
{
    public function __construct(
        private SiteService $siteService,
        private UpdateService $updateService,
        private ErrorReportingService $errorService,
    ) {}

    /**
     * Register a new site agent (uses registration token, not HMAC).
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'registration_token' => 'required|string',
            'site_url' => 'required|url',
            'wp_version' => 'nullable|string|max:20',
            'php_version' => 'nullable|string|max:20',
            'active_theme' => 'nullable|string|max:255',
            'server_software' => 'nullable|string|max:255',
            'plugin_version' => 'nullable|string|max:20',
        ]);

        $result = $this->siteService->completeRegistration(
            $validated['registration_token'],
            $validated,
        );

        if (! $result) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'invalid_token', 'message' => 'Registration token is invalid or expired.'],
            ], 401);
        }

        return response()->json([
            'success' => true,
            ...$result,
        ]);
    }

    /**
     * Receive heartbeat from agent (HMAC authenticated).
     */
    public function heartbeat(Request $request): JsonResponse
    {
        /** @var Site $site */
        $site = $request->attributes->get('site');

        $this->siteService->processHeartbeat($site, $request->all());

        return response()->json([
            'success' => true,
            'next_heartbeat_seconds' => 300,
        ]);
    }

    /**
     * Receive full sync from agent (HMAC authenticated).
     */
    public function sync(Request $request): JsonResponse
    {
        /** @var Site $site */
        $site = $request->attributes->get('site');

        $validated = $request->validate([
            'wp_version' => 'nullable|string|max:20',
            'php_version' => 'nullable|string|max:20',
            'active_theme' => 'nullable|string|max:255',
            'installed_items' => 'required|array',
            'installed_items.*.type' => 'required|in:plugin,theme,core',
            'installed_items.*.slug' => 'required|string',
            'installed_items.*.name' => 'required|string',
            'installed_items.*.current_version' => 'required|string',
            'installed_items.*.available_version' => 'nullable|string',
            'installed_items.*.is_active' => 'nullable|boolean',
            'installed_items.*.auto_update_enabled' => 'nullable|boolean',
            'installed_items.*.tested_wp_version' => 'nullable|string',
            'filesystem' => 'nullable|array',
        ]);

        // Update site metadata (store filesystem info in meta)
        $meta = $site->meta ?? [];
        if (! empty($validated['filesystem'])) {
            $meta['filesystem'] = $validated['filesystem'];
        }

        $site->update([
            'wp_version' => $validated['wp_version'] ?? $site->wp_version,
            'php_version' => $validated['php_version'] ?? $site->php_version,
            'active_theme' => $validated['active_theme'] ?? $site->active_theme,
            'meta' => $meta,
        ]);

        $count = $this->siteService->syncInstalledItems($site, $validated['installed_items']);

        return response()->json([
            'success' => true,
            'items_synced' => $count,
        ]);
    }

    /**
     * Receive update results from agent (HMAC authenticated).
     */
    public function updateResult(Request $request): JsonResponse
    {
        /** @var Site $site */
        $site = $request->attributes->get('site');

        $validated = $request->validate([
            'update_job_id' => 'required|integer',
            'items' => 'required|array',
            'items.*.update_job_item_id' => 'required|integer',
            'items.*.slug' => 'required|string',
            'items.*.type' => 'required|string',
            'items.*.old_version' => 'nullable|string',
            'items.*.resulting_version' => 'nullable|string',
            'items.*.status' => 'required|in:completed,failed',
            'items.*.raw_result' => 'nullable|array',
            'items.*.error_message' => 'nullable|string',
        ]);

        $job = UpdateJob::where('id', $validated['update_job_id'])
            ->where('site_id', $site->id)
            ->firstOrFail();

        $this->updateService->processUpdateResult($job, $validated['items']);

        return response()->json([
            'success' => true,
            'health_check_queued' => true,
        ]);
    }

    /**
     * Receive error reports from agent (HMAC authenticated).
     */
    public function errorReport(Request $request): JsonResponse
    {
        /** @var Site $site */
        $site = $request->attributes->get('site');

        $validated = $request->validate([
            'errors' => 'required|array',
            'errors.*.source' => 'required|string',
            'errors.*.severity' => 'required|in:info,warning,error,critical',
            'errors.*.message' => 'required|string',
            'errors.*.context' => 'nullable|array',
        ]);

        $count = $this->errorService->storeAgentErrors($site, $validated['errors']);

        return response()->json([
            'success' => true,
            'logged' => $count,
        ]);
    }
}
