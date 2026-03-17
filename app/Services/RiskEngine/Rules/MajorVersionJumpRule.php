<?php

namespace App\Services\RiskEngine\Rules;

use App\Models\Site;
use App\Models\UpdateJobItem;
use App\Services\RiskEngine\RiskRule;

class MajorVersionJumpRule implements RiskRule
{
    public function evaluate(UpdateJobItem $item, Site $site): ?array
    {
        $oldMajor = (int) explode('.', $item->old_version)[0];
        $newMajor = (int) explode('.', $item->requested_version)[0];

        if ($newMajor <= $oldMajor) {
            return null;
        }

        return [
            'rule' => 'major_version_jump',
            'description' => "Major version change from {$oldMajor}.x to {$newMajor}.x for {$item->slug}",
            'score' => 25,
            'data' => [
                'old' => $item->old_version,
                'new' => $item->requested_version,
                'slug' => $item->slug,
            ],
        ];
    }
}
