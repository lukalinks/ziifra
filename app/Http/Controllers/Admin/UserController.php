<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AdminAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query()
            ->withCount('organizations')
            ->orderByDesc('created_at');

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->boolean('super_admin')) {
            $query->where('is_super_admin', true);
        }

        return view('admin.users.index', [
            'users' => $query->paginate(config('admin.users_per_page', 25))->withQueryString(),
        ]);
    }

    public function show(User $user): View
    {
        $user->load(['organizations' => fn ($q) => $q->orderBy('name')]);

        return view('admin.users.show', [
            'user' => $user,
        ]);
    }

    public function updateSuperAdmin(Request $request, User $user, AdminAuditService $audit): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', __('admin.users.cannot_change_self'));
        }

        $grant = $request->boolean('is_super_admin');

        if ($grant === $user->isSuperAdmin()) {
            return back();
        }

        $user->update(['is_super_admin' => $grant]);

        $audit->log(
            $request->user(),
            $grant ? 'user.super_admin_granted' : 'user.super_admin_revoked',
            targetUser: $user,
            request: $request,
        );

        return back()->with(
            'status',
            $grant ? __('admin.users.super_admin_granted') : __('admin.users.super_admin_revoked'),
        );
    }
}
