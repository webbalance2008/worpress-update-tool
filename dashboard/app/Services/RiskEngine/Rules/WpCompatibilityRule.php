<?php

namespace App\Services\RiskEngine\Rules;

use App\Models\Site;
use App\Models\UpdateJobItem;
use App\Services\RiskEngine\RiskRule;

class WpCompatibilityRule implements RiskRule
{
    public function evaluate(UpdateJobItem $item, Site $site): ?array
    {
        if ($item->type !== 'plugin') {
            return null;
        }

        $installedItem = $item->installedItem;

        if (! $installedItem || ! $installedItem->tested_wp_version || ! $site->wp_version) {
            return null;
        }

        // Compare major.minor versions
        $testedParts = explode('.', $installedItem->tested_wp_version);
        $wpParts = explode('.', $site->wp_version);

        $testedMajorMinor = ($testedParts[0] ?? 0) . '.' . ($testedParts[1] ?? 0);
        $wpMajorMinor = ($wpParts[0] ?? 0) . '.' . ($wpParts[1] ?? 0);

        if (version_compare($testedMajorMinor, $wpMajorMinor, '>=')) {
            return null;
        }

        return [
            'rule' => 'wp_compatibility',
            'description' => "{$item->slug} is only tested up to WP {$installedItem->tested_wp_version} (site runs {$site->wp_version})",
            'score' => 15,
            'data' => [
                'slug' => $item->slug,
                'tested_wp' => $installedItem->tested_wp_version,
                'site_wp' => $site->wp_version,
            ],
        ];
    }
}
