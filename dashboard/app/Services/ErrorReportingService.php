<?php

namespace App\Services;

use App\Models\ErrorLog;
use App\Models\Site;

class ErrorReportingService
{
    /**
     * Store error reports from the agent.
     */
    public function storeAgentErrors(Site $site, array $errors, ?int $updateJobId = null): int
    {
        $count = 0;

        foreach ($errors as $error) {
            ErrorLog::create([
                'site_id' => $site->id,
                'update_job_id' => $updateJobId,
                'source' => $error['source'] ?? 'agent',
                'severity' => $error['severity'] ?? 'error',
                'message' => $error['message'],
                'context' => $error['context'] ?? null,
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * Create an error log entry from a health check failure.
     */
    public function logHealthCheckError(Site $site, string $checkName, array $checkResult, ?int $updateJobId = null): ErrorLog
    {
        return ErrorLog::create([
            'site_id' => $site->id,
            'update_job_id' => $updateJobId,
            'source' => 'health_check',
            'severity' => $checkResult['passed'] ? 'warning' : 'error',
            'message' => "Health check '{$checkName}' failed: HTTP {$checkResult['status_code'] ?? 'unknown'}",
            'context' => $checkResult,
        ]);
    }

    /**
     * Create an error log from an update failure.
     */
    public function logUpdateError(Site $site, int $updateJobId, string $message, array $context = []): ErrorLog
    {
        return ErrorLog::create([
            'site_id' => $site->id,
            'update_job_id' => $updateJobId,
            'source' => 'updater',
            'severity' => 'error',
            'message' => $message,
            'context' => $context,
        ]);
    }
}
