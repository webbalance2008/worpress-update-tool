<?php

namespace App\Services\RiskEngine\Rules;

use App\Models\Site;
use App\Models\UpdateJobItem;
use App\Services\RiskEngine\RiskRule;

class InactivePluginRule implements RiskRule
{
    public function evaluate(UpdateJobItem $item, Site $site): ?array
    {
        if ($item->type !== 'plugin') {
            return null;
        }

        $installedItem = $item->installedItem;

        if (! $installedItem || $installedItem->is_active) {
            return null;
        }

        // Lower risk — inactive plugins are less likely to break the site, but still worth noting
        return [
            'rule' => 'inactive_plugin',
            'description' => "{$item->slug} is inactive — consider whether this update is needed",
            'score' => -5, // Negative score = reduces risk
            'data' => [
                'slug' => $item->slug,
                'is_active' => false,
            ],
        ];
    }
}
