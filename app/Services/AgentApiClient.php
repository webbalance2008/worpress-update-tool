<?php

namespace App\Services;

use App\Models\Site;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AgentApiClient
{
    private const TIMEOUT_SECONDS = 60;
    private const CONNECT_TIMEOUT_SECONDS = 10;

    public function __construct(
        private HmacService $hmacService,
    ) {}

    /**
     * Send a signed request to an agent's REST API.
     */
    public function sendToAgent(Site $site, string $method, string $endpoint, array $data = []): ?array
    {
        $url = rtrim($site->url, '/') . '/wp-json/wum-agent/v1/' . ltrim($endpoint, '/');
        $path = '/wp-json/wum-agent/v1/' . ltrim($endpoint, '/');
        $body = ! empty($data) ? json_encode($data) : '';

        $headers = $this->hmacService->signRequest($site, strtoupper($method), $path, $body);
        $headers['Content-Type'] = 'application/json';
        $headers['Accept'] = 'application/json';

        try {
            /** @var Response $response */
            $response = Http::withHeaders($headers)
                ->timeout(self::TIMEOUT_SECONDS)
                ->connectTimeout(self::CONNECT_TIMEOUT_SECONDS)
                ->withBody($body, 'application/json')
                ->send($method, $url);

            if (! $response->successful()) {
                Log::warning('Agent API request failed', [
                    'site_id' => $site->id,
                    'url' => $url,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Agent API request exception', [
                'site_id' => $site->id,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Request the agent to execute updates.
     */
    public function executeUpdate(Site $site, int $updateJobId, array $items): ?array
    {
        return $this->sendToAgent($site, 'POST', 'execute-update', [
            'update_job_id' => $updateJobId,
            'items' => $items,
        ]);
    }

    /**
     * Check agent status.
     */
    public function checkStatus(Site $site): ?array
    {
        return $this->sendToAgent($site, 'POST', 'status', ['check' => 'status']);
    }
}
