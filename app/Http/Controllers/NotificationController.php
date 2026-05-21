<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function markRead(Request $request, string $notification): RedirectResponse
    {
        $user = $request->user();

        if (str_starts_with($notification, 'admin-')) {
            $dismissed = collect($request->session()->get('dismissed_admin_notifications', []));
            $request->session()->put('dismissed_admin_notifications', $dismissed->push($notification)->unique()->values()->all());

            return back();
        }

        /** @var DatabaseNotification|null $record */
        $record = $user->notifications()->whereKey($notification)->first();

        if ($record !== null) {
            $record->markAsRead();
        }

        return back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $user = $request->user();

        $user->unreadNotifications->markAsRead();

        if ($request->boolean('admin')) {
            $request->session()->put('dismissed_admin_notifications', [
                'admin-suspended',
                'admin-trial-expiring',
            ]);
        }

        return back();
    }
}
