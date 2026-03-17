<?php

namespace App\Services;

use App\Enums\HealthCheckStatus;
use App\Models\HealthCheck;
use App\Models\Site;
use App\Models\UpdateJob;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HealthCheckService
{
    /**
     * Patterns that indicate a critical WordPress error in response body.
     */
    private const CRITICAL_ERROR_PATTERNS = [
        'There has been a critical error on this website',
        'Fatal error',
        'Parse error',
        'Error establishing a database connection',
        'Briefly unavailable for scheduled maintenance',
    ];

    public function __construct(
        private ErrorReportingService $errorService,
    ) {}

    /**
     * Run all health checks for a site and store the results.
     */
    public function runChecks(Site $site, ?UpdateJob $updateJob = null): HealthCheck
    {
        $siteUrl = rtrim($site->url, '/');

        $checks = [
            'homepage' => $this->checkUrl($siteUrl),
            'wp_login' => $this->checkUrl($siteUrl . '/wp-login.php'),
            'rest_api' => $this->checkUrl($siteUrl . '/wp-json/wp/v2/', expectJson: true),
        ];

        // Version verification (only if this is a post-update check)
        if ($updateJob) {
            $checks['version_check'] = $this->checkVersions($updateJob, $site);
        }

        // Determine overall status
        $status = $this->determineOverallStatus($checks);

        $healthCheck = HealthCheck::create([
            'site_id' => $site->id,
            'update_job_id' => $updateJob?->id,
            'status' => $status,
            'checks' => $checks,
            'summary' => $this->buildSummary($checks, $status),
        ]);

        // Log errors for any failed checks
        foreach ($checks as $checkName => $checkResult) {
            if (! ($checkResult['passed'] ?? true)) {
                $this->errorService->logHealthCheckError($site, $checkName, $checkResult, $updateJob?->id);
            }
        }

        return $healthCheck;
    }

    /**
     * Check a URL for responsiveness and critical errors.
     */
    private function checkUrl(string $url, bool $expectJson = false): array
    {
        $startTime = microtime(true);

        try {
            $response = Http::timeout(15)->connectTimeout(5)->get($url);

            $responseTime = round((microtime(true) - $startTime) * 1000);
            $body = $response->body();
            $statusCode = $response->status();
            $hasCriticalError = $this->detectCriticalError($body);

            $passed = $statusCode >= 200
                && $statusCode < 400
                && ! $hasCriticalError;

            return [
                'url' => $url,
                'status_code' => $statusCode,
                'response_time_ms' => $responseTime,
                'has_critical_error' => $hasCriticalError,
                'critical_error_match' => $hasCriticalError ? $this->findCriticalErrorMatch($body) : null,
                'passed' => $passed,
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return [
                'url' => $url,
                'status_code' => null,
                'response_time_ms' => round((microtime(true) - $startTime) * 1000),
                'has_critical_error' => false,
                'error' => 'Connection failed: ' . $e->getMessage(),
                'passed' => false,
            ];
        } catch (\Exception $e) {
            Log::warning('Health check request failed', ['url' => $url, 'error' => $e->getMessage()]);

            return [
                'url' => $url,
                'status_code' => null,
                'response_time_ms' => null,
                'error' => $e->getMessage(),
                'passed' => false,
            ];
        }
    }

    /**
     * Verify that target versions changed as expected after an update.
     */
    private function checkVersions(UpdateJob $updateJob, Site $site): array
    {
        $updateJob->load('items');
        $results = [];
        $allPassed = true;

        foreach ($updateJob->items as $item) {
            if ($item->status !== 'completed') {
                continue;
            }

            $matched = $item->resulting_version === $item->requested_version;
            if (! $matched) {
                $allPassed = false;
            }

            $results[] = [
                'slug' => $item->slug,
                'type' => $item->type,
                'expected' => $item->requested_version,
                'actual' => $item->resulting_version,
                'matched' => $matched,
            ];
        }

        return [
            'items' => $results,
            'passed' => $allPassed,
        ];
    }

    /**
     * Detect critical error patterns in response body.
     */
    private function detectCriticalError(string $body): bool
    {
        foreach (self::CRITICAL_ERROR_PATTERNS as $pattern) {
            if (stripos($body, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Find which critical error pattern matched.
     */
    private function findCriticalErrorMatch(string $body): ?string
    {
        foreach (self::CRITICAL_ERROR_PATTERNS as $pattern) {
            if (stripos($body, $pattern) !== false) {
                return $pattern;
            }
        }

        return null;
    }

    /**
     * Determine overall health check status from individual check results.
     */
    private function determineOverallStatus(array $checks): HealthCheckStatus
    {
        $total = count($checks);
        $passed = 0;
        $criticalFailed = false;

        foreach ($checks as $name => $check) {
            if ($check['passed'] ?? false) {
                $passed++;
            } elseif (in_array($name, ['homepage', 'rest_api'])) {
                $criticalFailed = true;
            }
        }

        if ($criticalFailed) {
            return HealthCheckStatus::Failed;
        }

        if ($passed === $total) {
            return HealthCheckStatus::Passed;
        }

        return HealthCheckStatus::Degraded;
    }

    /**
     * Build a human-readable summary.
     */
    private function buildSummary(array $checks, HealthCheckStatus $status): string
    {
        $total = count($checks);
        $passed = collect($checks)->filter(fn ($c) => $c['passed'] ?? false)->count();

        return match ($status) {
            HealthCheckStatus::Passed => "All {$total} checks passed.",
            HealthCheckStatus::Degraded => "{$passed}/{$total} checks passed. Some non-critical checks failed.",
            HealthCheckStatus::Failed => "{$passed}/{$total} checks passed. Critical checks failed — site may be down.",
            default => 'Health check pending.',
        };
    }
}
