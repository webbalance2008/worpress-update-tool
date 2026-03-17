<?php

namespace App\Services\RiskEngine\Rules;

use App\Models\Site;
use App\Models\UpdateJobItem;
use App\Services\RiskEngine\RiskRule;

class BatchSizeRule implements RiskRule
{
    private const THRESHOLD = 5;

    public function evaluate(UpdateJobItem $item, Site $site): ?array
    {
        // Only evaluate on the first item to avoid duplicates
        $job = $item->updateJob;

        if ($item->id !== $job->items()->first()?->id) {
            return null;
        }

        $count = $job->items()->count();

        if ($count <= self::THRESHOLD) {
            return null;
        }

        return [
            'rule' => 'large_batch',
            'description' => "Updating {$count} items at once increases risk — consider smaller batches",
            'score' => min(20, ($count - self::THRESHOLD) * 3),
            'data' => [
                'item_count' => $count,
                'threshold' => self::THRESHOLD,
            ],
        ];
    }
}
