<?php

namespace App\Services;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\WorkspaceNavItem;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class WorkspaceNavItemService
{
    public const MAX_ADMIN_ROLES = 3;

    public function create(Organization $organization, User $user, array $data): WorkspaceNavItem
    {
        $sortOrder = (int) (WorkspaceNavItem::query()->where('organization_id', $organization->id)->max('sort_order') ?? 0) + 1;

        return WorkspaceNavItem::query()->create([
            'organization_id' => $organization->id,
            'created_by_user_id' => $user->id,
            'label' => $data['label'],
            'url' => $data['url'],
            'sort_order' => $sortOrder,
        ]);
    }

    public function delete(WorkspaceNavItem $item): void
    {
        $item->delete();
    }

    public function assertAdminLimitNotExceeded(Organization $organization): void
    {
        $count = $organization->users()
            ->wherePivotIn('role', [OrganizationRole::Admin->value, OrganizationRole::Owner->value])
            ->count();

        if ($count >= self::MAX_ADMIN_ROLES) {
            throw ValidationException::withMessages([
                'role' => __('team.errors.admin_limit', ['max' => self::MAX_ADMIN_ROLES]),
            ]);
        }
    }
}
