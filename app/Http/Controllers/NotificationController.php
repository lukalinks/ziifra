<?php

namespace App\Http\Controllers;

use App\Services\AdminPlatformService;
use App\Support\CurrentOrganization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Symfony\Component\HttpFoundation\Response;

class NotificationController extends Controller
{
    public function __construct(
        protected AdminPlatformService $platform,
    ) {}

    public function markRead(Request $request, string $notification): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        if (str_starts_with($notification, 'admin-')) {
            $dismissed = collect($request->session()->get('dismissed_admin_notifications', []));
            $request->session()->put('dismissed_admin_notifications', $dismissed->push($notification)->unique()->values()->all());

            return $this->respond($request);
        }

        /** @var DatabaseNotification|null $record */
        $record = $user->notifications()->whereKey($notification)->first();

        if ($record !== null) {
            $record->markAsRead();
        }

        return $this->respond($request);
    }

    public function markAllRead(Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        if ($request->boolean('admin')) {
            $this->dismissAllAdminEphemeral($request);
            $user->unreadNotifications->markAsRead();

            return $this->respond($request);
        }

        $organization = CurrentOrganization::get();

        if ($organization !== null) {
            $orgId = $organization->id;

            $user->unreadNotifications()
                ->get()
                ->filter(function (DatabaseNotification $notification) use ($orgId): bool {
                    $data = $notification->data;
                    $notificationOrgId = $data['organization_id'] ?? null;

                    return $notificationOrgId === null || (int) $notificationOrgId === $orgId;
                })
                ->each->markAsRead();
        } else {
            $user->unreadNotifications->markAsRead();
        }

        return $this->respond($request);
    }

    protected function dismissAllAdminEphemeral(Request $request): void
    {
        $dismissed = collect($request->session()->get('dismissed_admin_notifications', []));

        $keys = collect(['admin-suspended', 'admin-trial-expiring']);

        foreach ($this->platform->recentOrganizations(5) as $organization) {
            if ($organization->created_at !== null && $organization->created_at->lt(now()->subDays(7))) {
                continue;
            }

            $keys->push('admin-org-'.$organization->id);
        }

        $request->session()->put(
            'dismissed_admin_notifications',
            $dismissed->merge($keys)->unique()->values()->all(),
        );
    }

    protected function respond(Request $request): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back();
    }
}
