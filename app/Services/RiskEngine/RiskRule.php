<?php

namespace App\Services\RiskEngine;

use App\Models\Site;
use App\Models\UpdateJobItem;

/**
 * Interface for risk assessment rules.
 *
 * Each rule evaluates a single risk factor and returns a result
 * if the rule triggers, or null if it does not apply.
 */
interface RiskRule
{
    /**
     * Evaluate this rule against an update item in the context of a site.
     *
     * @return array{rule: string, description: string, score: int, data: array}|null
     */
    public function evaluate(UpdateJobItem $item, Site $site): ?array;
}
