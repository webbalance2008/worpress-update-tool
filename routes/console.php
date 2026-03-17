<?php

use App\Jobs\ScheduledSiteUpdateJob;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

// Run scheduled auto-updates: 1 site per hour for each user who has it enabled
Schedule::call(function () {
    User::all()->each(function (User $user) {
        $enabled = Cache::get("auto_updates_enabled_{$user->id}", false);

        if ($enabled) {
            ScheduledSiteUpdateJob::dispatch($user);
        }
    });
})->hourly()->name('auto-update-sites')->withoutOverlapping();
