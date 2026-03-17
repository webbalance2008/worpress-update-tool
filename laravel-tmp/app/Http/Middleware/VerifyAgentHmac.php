<?php

namespace App\Http\Middleware;

use App\Models\Site;
use App\Services\HmacService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyAgentHmac
{
    public function __construct(
        private HmacService $hmacService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $siteId = $request->header('X-WUM-Site-ID');

        if (! $siteId) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'missing_site_id', 'message' => 'X-WUM-Site-ID header is required.'],
            ], 401);
        }

        $site = Site::find($siteId);

        if (! $site || ! $site->auth_secret) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'site_not_found', 'message' => 'Site not found or not configured.'],
            ], 401);
        }

        if (! $this->hmacService->verifyRequest($request, $site)) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'invalid_signature', 'message' => 'Request signature validation failed.'],
            ], 401);
        }

        // Make the authenticated site available to downstream handlers
        $request->attributes->set('site', $site);

        return $next($request);
    }
}
