<?php

namespace App\Http\Controllers;

use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $sites = Site::forUser($request->user())
            ->withCount(['installedItems as pending_updates_count' => function ($query) {
                $query->whereNotNull('available_version');
            }])
            ->latest('last_seen_at')
            ->get();

        $stats = [
            'total_sites' => $sites->count(),
            'connected_sites' => $sites->where('status', 'connected')->count(),
            'total_pending_updates' => $sites->sum('pending_updates_count'),
        ];

        $autoUpdatesEnabled = Cache::get("auto_updates_enabled_{$request->user()->id}", false);

        return view('dashboard.index', compact('sites', 'stats', 'autoUpdatesEnabled'));
    }
}
