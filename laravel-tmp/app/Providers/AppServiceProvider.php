<?php

namespace App\Providers;

use App\Models\Site;
use App\Policies\SitePolicy;
use App\Services\RiskAnalyzerInterface;
use App\Services\RiskAssessmentService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(RiskAnalyzerInterface::class, RiskAssessmentService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Site::class, SitePolicy::class);
    }
}
