<?php

namespace App\Services;

use App\Models\RiskAssessment;
use App\Models\UpdateJob;

/**
 * Abstraction for risk analysis.
 *
 * MVP: RiskAssessmentService (rules engine).
 * Future: LlmRiskAnalyzer that calls an AI service for richer summaries.
 *
 * To swap implementations, bind this interface in a service provider:
 *   $this->app->bind(RiskAnalyzerInterface::class, LlmRiskAnalyzer::class);
 */
interface RiskAnalyzerInterface
{
    public function assess(UpdateJob $job): RiskAssessment;
}
