<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\User;
use App\Support\CurrentOrganization;
use App\Support\NotificationFeed;
use App\Support\NotificationItem;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;

class NotificationFeedService
{
    public function __construct(
        protected AdminPlatformService $platform,
    ) {}

    public function forAuthenticatedUser(?Organization $organization = null, bool $adminContext = false): NotificationFeed
    {
        $user = auth()->user();

        if ($user === null) {
            return new NotificationFeed(collect(), 0, false);
        }

        if ($adminContext && $user->isSuperAdmin()) {
            return $this->adminFeed($user);
        }

        return $this->workspaceFeed($user, $organization ?? CurrentOrganization::get());
    }

    public function workspaceFeed(User $user, ?Organization $organization): NotificationFeed
    {
        $notifications = $user->notifications()->latest()->limit(50)->get();

        if ($organization !== null) {
            $orgId = $organization->id;
            $notifications = $notifications
                ->filter(function (DatabaseNotification $notification) use ($orgId): bool {
                    $data = $notification->data;
                    $notificationOrgId = $data['organization_id'] ?? null;

                    return $notificationOrgId === null || (int) $notificationOrgId === $orgId;
                })
                ->take(20)
                ->values();
        } else {
            $notifications = $notifications->take(20);
        }

        $items = $notifications->map(fn (DatabaseNotification $notification) => $this->mapDatabaseNotification($notification));

        $unreadCount = $items->where('read', false)->count();

        return new NotificationFeed(
            $items,
            $unreadCount,
            true,
        );
    }

    public function adminFeed(User $user): NotificationFeed
    {
        $dismissed = collect(session('dismissed_admin_notifications', []));
        $items = collect();

        $suspendedCount = Organization::query()->whereNotNull('suspended_at')->count();

        if ($suspendedCount > 0 && ! $dismissed->contains('admin-suspended')) {
            $items->push(new NotificationItem(
                id: 'admin-suspended',
                title: __('notifications.admin_suspended_title'),
                body: trans_choice('notifications.admin_suspended_body', $suspendedCount, ['count' => $suspendedCount]),
                url: route('admin.organizations.index'),
                read: false,
                createdAt: now(),
                ephemeral: true,
            ));
        }

        $trialExpiring = $this->platform->trialExpiringSoonCount();

        if ($trialExpiring > 0 && ! $dismissed->contains('admin-trial-expiring')) {
            $items->push(new NotificationItem(
                id: 'admin-trial-expiring',
                title: __('notifications.admin_trial_expiring_title'),
                body: trans_choice('notifications.admin_trial_expiring_body', $trialExpiring, ['count' => $trialExpiring]),
                url: route('admin.organizations.index'),
                read: false,
                createdAt: now(),
                ephemeral: true,
            ));
        }

        foreach ($this->platform->recentOrganizations(5) as $organization) {
            if ($organization->created_at !== null && $organization->created_at->lt(now()->subDays(7))) {
                continue;
            }

            $key = 'admin-org-'.$organization->id;

            if ($dismissed->contains($key)) {
                continue;
            }

            $items->push(new NotificationItem(
                id: $key,
                title: __('notifications.admin_new_org_title'),
                body: __('notifications.admin_new_org_body', ['name' => $organization->name]),
                url: route('admin.organizations.show', $organization),
                read: false,
                createdAt: $organization->created_at ?? now(),
                ephemeral: true,
            ));
        }

        $databaseItems = $user->notifications()->latest()->limit(10)->get()
            ->map(fn (DatabaseNotification $notification) => $this->mapDatabaseNotification($notification));

        $items = $items->merge($databaseItems)->sortByDesc(fn (NotificationItem $item) => $item->createdAt)->values();

        return new NotificationFeed(
            $items,
            $items->where('read', false)->count(),
            true,
        );
    }

    protected function mapDatabaseNotification(DatabaseNotification $notification): NotificationItem
    {
        /** @var array{title?: string, body?: string, url?: string|null, icon?: string|null} $data */
        $data = $notification->data;

        return new NotificationItem(
            id: $notification->id,
            title: (string) ($data['title'] ?? ''),
            body: (string) ($data['body'] ?? ''),
            url: $data['url'] ?? null,
            read: $notification->read_at !== null,
            createdAt: $notification->created_at ?? now(),
            ephemeral: false,
        );
    }
}
