<?php

namespace App\Services;

use App\Enums\RiskLevel;
use App\Models\RiskAssessment;
use App\Models\UpdateJob;
use App\Services\RiskEngine\RiskRule;
use App\Services\RiskEngine\Rules\BatchSizeRule;
use App\Services\RiskEngine\Rules\CoreUpdateRule;
use App\Services\RiskEngine\Rules\InactivePluginRule;
use App\Services\RiskEngine\Rules\MajorVersionJumpRule;
use App\Services\RiskEngine\Rules\OutdatedPhpRule;
use App\Services\RiskEngine\Rules\SensitiveCategoryRule;
use App\Services\RiskEngine\Rules\WpCompatibilityRule;

/**
 * Risk assessment service.
 *
 * MVP implementation uses a rules engine. The RiskAnalyzerInterface abstraction
 * allows plugging in an LLM-backed analyzer in the future without changing
 * the rest of the system.
 */
class RiskAssessmentService implements RiskAnalyzerInterface
{
    /**
     * Registered risk rules, evaluated in order.
     *
     * @return RiskRule[]
     */
    protected function rules(): array
    {
        return [
            new MajorVersionJumpRule(),
            new SensitiveCategoryRule(),
            new InactivePluginRule(),
            new WpCompatibilityRule(),
            new OutdatedPhpRule(),
            new CoreUpdateRule(),
            new BatchSizeRule(),
        ];
    }

    /**
     * Run risk assessment for an update job.
     */
    public function assess(UpdateJob $job): RiskAssessment
    {
        $site = $job->site;
        $job->load('items.installedItem');

        $allFactors = [];

        foreach ($job->items as $item) {
            foreach ($this->rules() as $rule) {
                $result = $rule->evaluate($item, $site);
                if ($result !== null) {
                    $allFactors[] = $result;
                }
            }
        }

        // Deduplicate by rule name (some rules are site-level, not per-item)
        $uniqueFactors = collect($allFactors)
            ->unique(fn ($f) => $f['rule'] . ':' . ($f['data']['slug'] ?? 'site'))
            ->values()
            ->toArray();

        $score = max(0, min(100, array_sum(array_column($uniqueFactors, 'score'))));
        $level = RiskLevel::fromScore($score);
        $explanation = $this->buildExplanation($score, $level, $uniqueFactors);

        return RiskAssessment::create([
            'update_job_id' => $job->id,
            'site_id' => $site->id,
            'score' => $score,
            'level' => $level,
            'explanation' => $explanation,
            'factors' => $uniqueFactors,
        ]);
    }

    /**
     * Build a human-readable explanation.
     */
    private function buildExplanation(int $score, RiskLevel $level, array $factors): string
    {
        if (empty($factors)) {
            return 'No significant risk factors detected. This update appears safe to proceed.';
        }

        $factorCount = count($factors);
        $levelLabel = ucfirst($level->value);

        $explanation = "{$levelLabel} risk (score: {$score}/100). ";
        $explanation .= "{$factorCount} risk factor" . ($factorCount > 1 ? 's' : '') . " detected: ";

        $descriptions = array_map(fn ($f) => $f['description'], $factors);
        $explanation .= implode('; ', $descriptions) . '.';

        return $explanation;
    }
}
