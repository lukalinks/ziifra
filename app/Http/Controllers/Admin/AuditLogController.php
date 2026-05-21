<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\User;
use App\Services\AdminPlatformService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request, AdminPlatformService $platform): View
    {
        $query = AdminAuditLog::query()
            ->with(['admin', 'organization', 'targetUser'])
            ->latest();

        if ($action = $request->string('action')->trim()->toString()) {
            $query->where('action', $action);
        }

        if ($adminId = $request->integer('admin_id')) {
            $query->where('admin_user_id', $adminId);
        }

        if ($organizationId = $request->integer('organization_id')) {
            $query->where('organization_id', $organizationId);
        }

        $actions = AdminAuditLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        $adminIds = AdminAuditLog::query()->distinct()->pluck('admin_user_id');

        $admins = User::query()
            ->where(function ($query) use ($adminIds): void {
                $query->where('is_super_admin', true)
                    ->orWhereIn('id', $adminIds);
            })
            ->orderBy('email')
            ->get(['id', 'name', 'email']);

        return view('admin.audit-logs.index', [
            'logs' => $query->paginate(config('admin.audit_log_per_page', 50))->withQueryString(),
            'actions' => $actions,
            'admins' => $admins,
            'platform' => $platform,
        ]);
    }
}
