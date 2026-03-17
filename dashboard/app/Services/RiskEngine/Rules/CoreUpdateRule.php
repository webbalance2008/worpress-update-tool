<?php

namespace App\Services\RiskEngine\Rules;

use App\Models\Site;
use App\Models\UpdateJobItem;
use App\Services\RiskEngine\RiskRule;

class CoreUpdateRule implements RiskRule
{
    public function evaluate(UpdateJobItem $item, Site $site): ?array
    {
        if ($item->type !== 'core') {
            return null;
        }

        // Core updates are inherently higher risk
        return [
            'rule' => 'core_update',
            'description' => 'WordPress core updates carry inherent risk and affect the entire site',
            'score' => 20,
            'data' => [
                'old' => $item->old_version,
                'new' => $item->requested_version,
            ],
        ];
    }
}
