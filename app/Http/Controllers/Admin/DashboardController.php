<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use App\Services\AdminPlatformService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(AdminPlatformService $platform): View
    {
        return view('admin.dashboard', [
            'organizationCount' => Organization::query()->count(),
            'userCount' => User::query()->count(),
            'suspendedCount' => Organization::query()->whereNotNull('suspended_at')->count(),
            'trialCount' => Organization::query()->where('plan', 'trial')->count(),
            'trialExpiringCount' => $platform->trialExpiringSoonCount(),
            'paidWorkspaceCount' => $platform->paidWorkspaceCount(),
            'recentOrganizations' => $platform->recentOrganizations(),
            'recentAuditLogs' => $platform->recentAuditLogs(),
            'platform' => $platform,
        ]);
    }
}
