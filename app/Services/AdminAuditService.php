<?php

namespace App\Services;

use App\Models\AdminAuditLog;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;

class AdminAuditService
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function log(
        User $admin,
        string $action,
        ?Organization $organization = null,
        ?User $targetUser = null,
        ?array $metadata = null,
        ?Request $request = null,
    ): AdminAuditLog {
        return AdminAuditLog::query()->create([
            'admin_user_id' => $admin->id,
            'action' => $action,
            'organization_id' => $organization?->id,
            'target_user_id' => $targetUser?->id,
            'metadata' => $metadata,
            'ip_address' => $request?->ip(),
        ]);
    }
}
