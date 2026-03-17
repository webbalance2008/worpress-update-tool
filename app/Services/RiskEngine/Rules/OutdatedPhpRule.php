<?php

namespace App\Services\RiskEngine\Rules;

use App\Models\Site;
use App\Models\UpdateJobItem;
use App\Services\RiskEngine\RiskRule;

class OutdatedPhpRule implements RiskRule
{
    /**
     * PHP versions considered outdated (EOL or close to it).
     */
    private const OUTDATED_THRESHOLD = '8.1';

    public function evaluate(UpdateJobItem $item, Site $site): ?array
    {
        if (! $site->php_version) {
            return null;
        }

        // Only trigger once per assessment, not per item — use a site-level check
        // We apply this to the first item only to avoid duplicate factors
        if ($item->update_job_id && $item->id !== $item->updateJob->items()->first()?->id) {
            return null;
        }

        if (version_compare($site->php_version, self::OUTDATED_THRESHOLD, '>=')) {
            return null;
        }

        return [
            'rule' => 'outdated_php',
            'description' => "Site runs PHP {$site->php_version} which is outdated — updates may require newer PHP",
            'score' => 10,
            'data' => [
                'php_version' => $site->php_version,
                'threshold' => self::OUTDATED_THRESHOLD,
            ],
        ];
    }
}
